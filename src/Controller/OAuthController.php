<?php

namespace App\Controller;

use App\Entity\LoginHistory;
use App\Entity\OAuthConnection;
use App\Entity\RefreshToken;
use App\Entity\User;
use App\Repository\OAuthConnectionRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller pour l'authentification OAuth2/OIDC
 *
 * Gère la connexion via Google, GitHub et Keycloak.
 * Supporte à la fois la création de compte et le lien avec un compte existant.
 */
#[Route('/auth/oauth', name: 'app_auth_oauth_')]
final class OAuthController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private OAuthConnectionRepository $oauthConnectionRepository,
        private JWTTokenManagerInterface $jwtManager,
        private UserPasswordHasherInterface $passwordHasher,
        private LoggerInterface $logger,
    ) {}

    /**
     * Get available OAuth providers and their configuration
     */
    #[Route('/providers', name: 'providers', methods: ['GET'], options: ['description' => 'Liste les fournisseurs OAuth disponibles'])]
    public function getProviders(): JsonResponse
    {
        $providers = [];

        // Check which providers are configured
        if (!empty($_ENV['OAUTH_GOOGLE_CLIENT_ID'] ?? '')) {
            $providers[] = [
                'name' => 'google',
                'displayName' => 'Google',
                'enabled' => true,
            ];
        }

        if (!empty($_ENV['OAUTH_GITHUB_CLIENT_ID'] ?? '')) {
            $providers[] = [
                'name' => 'github',
                'displayName' => 'GitHub',
                'enabled' => true,
            ];
        }

        if (!empty($_ENV['OAUTH_KEYCLOAK_CLIENT_ID'] ?? '')) {
            $providers[] = [
                'name' => 'keycloak',
                'displayName' => 'SSO (Keycloak)',
                'enabled' => true,
            ];
        }

        return $this->json([
            'status' => 'success',
            'data' => $providers
        ], 200);
    }

    /**
     * Initiate OAuth flow - returns the authorization URL
     *
     * The client should redirect the user to this URL
     */
    #[Route('/{provider}/connect', name: 'connect', methods: ['GET'], options: ['description' => 'Initier la connexion OAuth'])]
    public function connect(string $provider, Request $request): JsonResponse
    {
        if (!in_array($provider, OAuthConnection::VALID_PROVIDERS, true)) {
            return $this->json([
                'status' => 'error',
                'message' => 'Invalid OAuth provider'
            ], 400);
        }

        $config = $this->getProviderConfig($provider);
        if (!$config) {
            return $this->json([
                'status' => 'error',
                'message' => 'OAuth provider not configured'
            ], 400);
        }

        // Generate state for CSRF protection
        $state = bin2hex(random_bytes(32));

        // Store state in session or return for client to store
        $redirectUri = $request->query->get('redirect_uri', $config['redirect_uri']);

        $authUrl = $this->buildAuthorizationUrl($provider, $config, $state, $redirectUri);

        return $this->json([
            'status' => 'success',
            'data' => [
                'authorizationUrl' => $authUrl,
                'state' => $state,
                'provider' => $provider
            ]
        ], 200);
    }

    /**
     * Handle OAuth callback - exchange code for tokens
     *
     * This endpoint receives the authorization code from the OAuth provider
     * and exchanges it for access tokens, then authenticates or creates the user.
     */
    #[Route('/{provider}/callback', name: 'callback', methods: ['POST'], options: ['description' => 'Callback OAuth - échange le code contre des tokens'])]
    public function callback(string $provider, Request $request): JsonResponse
    {
        if (!in_array($provider, OAuthConnection::VALID_PROVIDERS, true)) {
            return $this->json([
                'status' => 'error',
                'message' => 'Invalid OAuth provider'
            ], 400);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['code'])) {
            return $this->json([
                'status' => 'error',
                'message' => 'Authorization code is required'
            ], 400);
        }

        $config = $this->getProviderConfig($provider);
        if (!$config) {
            return $this->json([
                'status' => 'error',
                'message' => 'OAuth provider not configured'
            ], 400);
        }

        try {
            // Exchange code for tokens
            $tokenData = $this->exchangeCodeForTokens(
                $provider,
                $config,
                $data['code'],
                $data['redirect_uri'] ?? $config['redirect_uri']
            );

            if (!$tokenData) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Failed to exchange authorization code'
                ], 400);
            }

            // Get user info from provider
            $providerUser = $this->getProviderUserInfo($provider, $tokenData['access_token']);

            if (!$providerUser || !isset($providerUser['id'])) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Failed to get user information from provider'
                ], 400);
            }

            // Check if OAuth connection already exists
            $oauthConnection = $this->oauthConnectionRepository->findByProviderAndUserId(
                $provider,
                (string) $providerUser['id']
            );

            if ($oauthConnection) {
                // Existing connection - authenticate user
                $user = $oauthConnection->getUser();

                // Update tokens
                $oauthConnection->updateTokens(
                    $tokenData['access_token'],
                    $tokenData['refresh_token'] ?? null,
                    $tokenData['expires_in'] ?? null
                );
                $oauthConnection->markAsUsed();
                $oauthConnection->setProviderData($providerUser);

                $this->entityManager->flush();

                return $this->authenticateUser($user, $request, "OAuth login via {$provider}");
            }

            // New connection - check if linking to existing account or creating new
            $email = $providerUser['email'] ?? null;
            $existingUser = $email ? $this->userRepository->findOneBy(['email' => $email]) : null;

            // Check if user is already authenticated (linking flow)
            $currentUser = $this->getUser();
            if ($currentUser instanceof User) {
                // Link OAuth to existing authenticated user
                return $this->linkOAuthToUser(
                    $currentUser,
                    $provider,
                    $providerUser,
                    $tokenData
                );
            }

            if ($existingUser) {
                // Email matches existing user - link accounts
                return $this->linkOAuthToUser(
                    $existingUser,
                    $provider,
                    $providerUser,
                    $tokenData
                );
            }

            // Create new user from OAuth
            return $this->createUserFromOAuth(
                $provider,
                $providerUser,
                $tokenData,
                $request
            );

        } catch (\Exception $e) {
            $this->logger->error('OAuth callback failed', [
                'provider' => $provider,
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'status' => 'error',
                'message' => 'OAuth authentication failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get current user's OAuth connections
     */
    #[Route('/connections', name: 'connections_list', methods: ['GET'], options: ['description' => 'Liste les connexions OAuth de l\'utilisateur'])]
    public function listConnections(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json([
                'status' => 'error',
                'message' => 'Not authenticated'
            ], 401);
        }

        $connections = $this->oauthConnectionRepository->findByUser($user);

        $data = array_map(function (OAuthConnection $conn) {
            return [
                'id' => $conn->getId(),
                'provider' => $conn->getProvider(),
                'providerDisplayName' => $conn->getProviderDisplayName(),
                'providerEmail' => $conn->getProviderEmail(),
                'connectedAt' => $conn->getConnectedAt()->format('c'),
                'lastUsedAt' => $conn->getLastUsedAt()?->format('c'),
            ];
        }, $connections);

        return $this->json([
            'status' => 'success',
            'data' => $data
        ], 200);
    }

    /**
     * Disconnect an OAuth provider
     */
    #[Route('/{provider}/disconnect', name: 'disconnect', methods: ['DELETE'], options: ['description' => 'Déconnecter un fournisseur OAuth'])]
    public function disconnect(string $provider, Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json([
                'status' => 'error',
                'message' => 'Not authenticated'
            ], 401);
        }

        if (!in_array($provider, OAuthConnection::VALID_PROVIDERS, true)) {
            return $this->json([
                'status' => 'error',
                'message' => 'Invalid OAuth provider'
            ], 400);
        }

        // Check if user has a password set (to ensure they can still log in)
        $hasPassword = !empty($user->getPassword());
        $connectionCount = count($this->oauthConnectionRepository->findByUser($user));

        if (!$hasPassword && $connectionCount <= 1) {
            return $this->json([
                'status' => 'error',
                'message' => 'Cannot disconnect the only authentication method. Please set a password first.'
            ], 400);
        }

        $deleted = $this->oauthConnectionRepository->deleteByUserAndProvider($user, $provider);

        if ($deleted === 0) {
            return $this->json([
                'status' => 'error',
                'message' => 'OAuth connection not found'
            ], 404);
        }

        return $this->json([
            'status' => 'success',
            'message' => 'OAuth provider disconnected successfully'
        ], 200);
    }

    /**
     * Get provider configuration from environment
     */
    private function getProviderConfig(string $provider): ?array
    {
        $baseConfig = [
            'google' => [
                'client_id' => $_ENV['OAUTH_GOOGLE_CLIENT_ID'] ?? '',
                'client_secret' => $_ENV['OAUTH_GOOGLE_CLIENT_SECRET'] ?? '',
                'authorization_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
                'token_url' => 'https://oauth2.googleapis.com/token',
                'userinfo_url' => 'https://www.googleapis.com/oauth2/v2/userinfo',
                'redirect_uri' => ($_ENV['FRONTEND_URL'] ?? 'http://localhost:3000') . '/auth/oauth/google/callback',
                'scope' => 'openid email profile',
            ],
            'github' => [
                'client_id' => $_ENV['OAUTH_GITHUB_CLIENT_ID'] ?? '',
                'client_secret' => $_ENV['OAUTH_GITHUB_CLIENT_SECRET'] ?? '',
                'authorization_url' => 'https://github.com/login/oauth/authorize',
                'token_url' => 'https://github.com/login/oauth/access_token',
                'userinfo_url' => 'https://api.github.com/user',
                'redirect_uri' => ($_ENV['FRONTEND_URL'] ?? 'http://localhost:3000') . '/auth/oauth/github/callback',
                'scope' => 'user:email',
            ],
            'keycloak' => [
                'client_id' => $_ENV['OAUTH_KEYCLOAK_CLIENT_ID'] ?? '',
                'client_secret' => $_ENV['OAUTH_KEYCLOAK_CLIENT_SECRET'] ?? '',
                'server_url' => $_ENV['OAUTH_KEYCLOAK_SERVER_URL'] ?? '',
                'realm' => $_ENV['OAUTH_KEYCLOAK_REALM'] ?? 'master',
                'redirect_uri' => ($_ENV['FRONTEND_URL'] ?? 'http://localhost:3000') . '/auth/oauth/keycloak/callback',
                'scope' => 'openid email profile',
            ],
        ];

        $config = $baseConfig[$provider] ?? null;

        if (!$config || empty($config['client_id'])) {
            return null;
        }

        // Build Keycloak URLs dynamically
        if ($provider === 'keycloak' && !empty($config['server_url'])) {
            $baseUrl = rtrim($config['server_url'], '/');
            $realm = $config['realm'];
            $config['authorization_url'] = "{$baseUrl}/realms/{$realm}/protocol/openid-connect/auth";
            $config['token_url'] = "{$baseUrl}/realms/{$realm}/protocol/openid-connect/token";
            $config['userinfo_url'] = "{$baseUrl}/realms/{$realm}/protocol/openid-connect/userinfo";
        }

        return $config;
    }

    /**
     * Build the authorization URL for the provider
     */
    private function buildAuthorizationUrl(string $provider, array $config, string $state, string $redirectUri): string
    {
        $params = [
            'client_id' => $config['client_id'],
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => $config['scope'],
            'state' => $state,
        ];

        // Provider-specific parameters
        if ($provider === 'google') {
            $params['access_type'] = 'offline';
            $params['prompt'] = 'consent';
        }

        return $config['authorization_url'] . '?' . http_build_query($params);
    }

    /**
     * Exchange authorization code for tokens
     */
    private function exchangeCodeForTokens(string $provider, array $config, string $code, string $redirectUri): ?array
    {
        $postData = [
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'code' => $code,
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code',
        ];

        $ch = curl_init($config['token_url']);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($postData),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/x-www-form-urlencoded',
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            $this->logger->error('Token exchange failed', [
                'provider' => $provider,
                'http_code' => $httpCode,
                'response' => $response
            ]);
            return null;
        }

        return json_decode($response, true);
    }

    /**
     * Get user information from the OAuth provider
     */
    private function getProviderUserInfo(string $provider, string $accessToken): ?array
    {
        $config = $this->getProviderConfig($provider);
        if (!$config) {
            return null;
        }

        $ch = curl_init($config['userinfo_url']);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Accept: application/json',
                'User-Agent: CRM-API/1.0', // Required for GitHub
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            $this->logger->error('User info fetch failed', [
                'provider' => $provider,
                'http_code' => $httpCode
            ]);
            return null;
        }

        $userData = json_decode($response, true);

        // Normalize user data across providers
        return $this->normalizeProviderUserData($provider, $userData);
    }

    /**
     * Normalize user data from different providers to a common format
     */
    private function normalizeProviderUserData(string $provider, array $userData): array
    {
        return match ($provider) {
            'google' => [
                'id' => $userData['id'],
                'email' => $userData['email'] ?? null,
                'name' => $userData['name'] ?? null,
                'firstName' => $userData['given_name'] ?? null,
                'lastName' => $userData['family_name'] ?? null,
                'picture' => $userData['picture'] ?? null,
            ],
            'github' => [
                'id' => (string) $userData['id'],
                'email' => $userData['email'] ?? null,
                'name' => $userData['name'] ?? $userData['login'],
                'firstName' => null,
                'lastName' => null,
                'picture' => $userData['avatar_url'] ?? null,
                'login' => $userData['login'],
            ],
            'keycloak' => [
                'id' => $userData['sub'],
                'email' => $userData['email'] ?? null,
                'name' => $userData['name'] ?? null,
                'firstName' => $userData['given_name'] ?? null,
                'lastName' => $userData['family_name'] ?? null,
                'picture' => null,
            ],
            default => $userData,
        };
    }

    /**
     * Link OAuth connection to an existing user
     */
    private function linkOAuthToUser(User $user, string $provider, array $providerUser, array $tokenData): JsonResponse
    {
        // Check if user already has this provider connected
        if ($this->oauthConnectionRepository->hasProviderConnection($user, $provider)) {
            return $this->json([
                'status' => 'error',
                'message' => 'This provider is already connected to your account'
            ], 400);
        }

        $oauthConnection = new OAuthConnection($user, $provider, (string) $providerUser['id']);
        $oauthConnection->setProviderEmail($providerUser['email'] ?? null);
        $oauthConnection->updateTokens(
            $tokenData['access_token'],
            $tokenData['refresh_token'] ?? null,
            $tokenData['expires_in'] ?? null
        );
        $oauthConnection->setProviderData($providerUser);

        $this->entityManager->persist($oauthConnection);
        $this->entityManager->flush();

        return $this->json([
            'status' => 'success',
            'message' => 'OAuth provider linked successfully',
            'data' => [
                'provider' => $provider,
                'providerEmail' => $providerUser['email'] ?? null,
            ]
        ], 200);
    }

    /**
     * Create a new user from OAuth data
     */
    private function createUserFromOAuth(string $provider, array $providerUser, array $tokenData, Request $request): JsonResponse
    {
        if (!isset($providerUser['email'])) {
            return $this->json([
                'status' => 'error',
                'message' => 'Email is required for registration. Please ensure your OAuth provider shares your email.'
            ], 400);
        }

        // Generate unique user code
        $userCode = 'USER_' . strtoupper(bin2hex(random_bytes(4)));

        // Create new user
        $user = new User($providerUser['email'], [
            'roles' => ['ROLE_USER'],
            'code' => $userCode
        ]);

        // Set name from OAuth data
        if (isset($providerUser['firstName'])) {
            $user->setFirstname($providerUser['firstName']);
        } elseif (isset($providerUser['name'])) {
            $nameParts = explode(' ', $providerUser['name'], 2);
            $user->setFirstname($nameParts[0]);
            if (isset($nameParts[1])) {
                $user->setLastname($nameParts[1]);
            }
        }

        if (isset($providerUser['lastName'])) {
            $user->setLastname($providerUser['lastName']);
        }

        // User is active since OAuth verifies the email
        $user->setIsActive(true);

        // Set a random password (user won't need it for OAuth login)
        $randomPassword = bin2hex(random_bytes(32));
        $hashedPassword = $this->passwordHasher->hashPassword($user, $randomPassword);
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);

        // Create OAuth connection
        $oauthConnection = new OAuthConnection($user, $provider, (string) $providerUser['id']);
        $oauthConnection->setProviderEmail($providerUser['email']);
        $oauthConnection->updateTokens(
            $tokenData['access_token'],
            $tokenData['refresh_token'] ?? null,
            $tokenData['expires_in'] ?? null
        );
        $oauthConnection->setProviderData($providerUser);

        $this->entityManager->persist($oauthConnection);
        $this->entityManager->flush();

        // Authenticate the new user
        return $this->authenticateUser($user, $request, "OAuth registration via {$provider}");
    }

    /**
     * Authenticate user and return JWT tokens
     */
    private function authenticateUser(User $user, Request $request, string $loginMethod = 'OAuth'): JsonResponse
    {
        // Generate JWT token
        $token = $this->jwtManager->create($user);

        // Create refresh token
        $refreshToken = new RefreshToken(
            $user,
            $request->getClientIp(),
            $request->headers->get('User-Agent')
        );
        $this->entityManager->persist($refreshToken);

        // Log the login
        $loginHistory = new LoginHistory(
            $user,
            $request->getClientIp() ?? 'unknown',
            $request->headers->get('User-Agent') ?? 'unknown',
            true,
            $loginMethod
        );
        $this->entityManager->persist($loginHistory);

        $this->entityManager->flush();

        return $this->json([
            'status' => 'success',
            'message' => 'Authentication successful',
            'data' => [
                'token' => $token,
                'refreshToken' => $refreshToken->getPlainToken(),
                'expiresAt' => $refreshToken->getExpiresAt()->format('c'),
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'firstname' => $user->getFirstname(),
                    'lastname' => $user->getLastname(),
                    'roles' => $user->getRoles()
                ]
            ]
        ], 200);
    }
}
