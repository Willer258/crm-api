<?php

namespace App\Controller;

use App\Entity\EmailOtp;
use App\Entity\LoginHistory;
use App\Entity\MagicLink;
use App\Entity\PasswordResetToken;
use App\Entity\RefreshToken;
use App\Entity\User;
use App\Repository\EmailOtpRepository;
use App\Repository\MagicLinkRepository;
use App\Repository\PasswordResetTokenRepository;
use App\Repository\RefreshTokenRepository;
use App\Repository\UserRepository;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Psr\Log\LoggerInterface;

#[Route('/auth', name: 'app_auth_')]
final class AuthController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private ValidatorInterface $validator,
        private JWTTokenManagerInterface $jwtManager,
        private RefreshTokenRepository $refreshTokenRepository,
        private PasswordResetTokenRepository $passwordResetTokenRepository,
        private EmailOtpRepository $emailOtpRepository,
        private MagicLinkRepository $magicLinkRepository,
        private EmailService $emailService,
        private LoggerInterface $logger,
    ) {}

    /**
     * Register a new user account
     */
    #[Route('/register', name: 'register', methods: ['POST'], options: ['description' => 'Créer un nouveau compte utilisateur'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Validate required fields
        if (!isset($data['email']) || !isset($data['password'])) {
            return $this->json([
                'status' => 'error',
                'message' => 'Email and password are required'
            ], 400);
        }

        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return $this->json([
                'status' => 'error',
                'message' => 'Invalid email format'
            ], 400);
        }

        // Check if user already exists
        $existingUser = $this->userRepository->findOneBy(['email' => $data['email']]);
        if ($existingUser) {
            return $this->json([
                'status' => 'error',
                'message' => 'User with this email already exists'
            ], 409);
        }

        // Validate password strength
        if (strlen($data['password']) < 8) {
            return $this->json([
                'status' => 'error',
                'message' => 'Password must be at least 8 characters long'
            ], 400);
        }

        try {
            // Generate unique user code
            $userCode = 'USER_' . strtoupper(bin2hex(random_bytes(4)));

            // Create new user
            $user = new User($data['email'], [
                'roles' => ['ROLE_USER'],
                'code' => $userCode
            ]);

            $user->setFirstname($data['firstName'] ?? '');
            $user->setLastname($data['lastName'] ?? '');
            $user->setIsActive(false); // User must verify email first

            // Hash password
            $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);

            // Validate entity
            $errors = $this->validator->validate($user);
            if (count($errors) > 0) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => (string) $errors
                ], 400);
            }

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            // Create and send OTP for email verification
            $otp = new EmailOtp($user->getEmail(), 'email_verification', $user, $request->getClientIp());
            $this->entityManager->persist($otp);
            $this->entityManager->flush();

            // Send OTP via email
            $emailSent = $this->emailService->sendOtpCode($otp, $user);

            if (!$emailSent) {
                $this->logger->warning('Failed to send OTP email, but user was created', [
                    'user_id' => $user->getId()
                ]);
            }

            $responseData = [
                'userId' => $user->getId(),
                'email' => $user->getEmail(),
            ];

            // Only include OTP code in development environment for testing
            if ($_ENV['APP_ENV'] === 'dev') {
                $responseData['otpCode'] = $otp->getCode();
            }

            return $this->json([
                'status' => 'success',
                'message' => 'User registered successfully. Please check your email for the verification code.',
                'data' => $responseData
            ], 201);

        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => 'Failed to register user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify email address with OTP
     */
    #[Route('/verify-email-otp', name: 'verify_email_otp', methods: ['POST'], options: ['description' => 'Vérifier l\'adresse email avec code OTP'])]
    public function verifyEmailOtp(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email']) || !isset($data['code'])) {
            return $this->json([
                'status' => 'error',
                'message' => 'Email and verification code are required'
            ], 400);
        }

        // Rate limiting: check attempts from this IP
        $recentAttempts = $this->emailOtpRepository->countRecentAttempts($request->getClientIp() ?? 'unknown', 15);
        if ($recentAttempts > 10) {
            return $this->json([
                'status' => 'error',
                'message' => 'Too many verification attempts. Please try again later.'
            ], 429);
        }

        // Find valid OTP
        $otp = $this->emailOtpRepository->findValidOtp($data['email'], 'email_verification');

        if (!$otp) {
            return $this->json([
                'status' => 'error',
                'message' => 'No valid verification code found. Please request a new one.'
            ], 404);
        }

        // Verify the code
        if (!$otp->verify($data['code'])) {
            $this->entityManager->flush(); // Save the attempt increment

            $attemptsLeft = 5 - $otp->getAttempts();
            return $this->json([
                'status' => 'error',
                'message' => 'Invalid verification code.',
                'attemptsLeft' => max(0, $attemptsLeft)
            ], 400);
        }

        try {
            // Find user by email
            $user = $this->userRepository->findOneBy(['email' => $data['email']]);

            if (!$user) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'User not found'
                ], 404);
            }

            // Activate user account
            $user->setIsActive(true);
            $otp->markAsUsed();

            $this->entityManager->flush();

            // Send welcome email
            $this->emailService->sendWelcomeEmail($user);

            return $this->json([
                'status' => 'success',
                'message' => 'Email verified successfully. You can now log in.',
                'data' => [
                    'userId' => $user->getId(),
                    'email' => $user->getEmail()
                ]
            ], 200);

        } catch (\Exception $e) {
            $this->logger->error('Email verification failed', [
                'email' => $data['email'],
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'status' => 'error',
                'message' => 'Failed to verify email: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Resend OTP verification code
     */
    #[Route('/resend-otp', name: 'resend_otp', methods: ['POST'], options: ['description' => 'Renvoyer le code OTP de vérification'])]
    public function resendOtp(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email'])) {
            return $this->json([
                'status' => 'error',
                'message' => 'Email is required'
            ], 400);
        }

        // Rate limiting
        $recentAttempts = $this->emailOtpRepository->countRecentAttempts($request->getClientIp() ?? 'unknown', 15);
        if ($recentAttempts > 5) {
            return $this->json([
                'status' => 'error',
                'message' => 'Too many requests. Please wait before requesting a new code.'
            ], 429);
        }

        $user = $this->userRepository->findOneBy(['email' => $data['email']]);

        if (!$user) {
            // Don't reveal if user exists or not for security
            return $this->json([
                'status' => 'success',
                'message' => 'If an account exists with this email, a new verification code will be sent.'
            ], 200);
        }

        if ($user->getIsActive()) {
            return $this->json([
                'status' => 'error',
                'message' => 'Email is already verified'
            ], 400);
        }

        try {
            // Invalidate old OTPs
            $this->emailOtpRepository->invalidateEmailOtps($data['email'], 'email_verification');

            // Create new OTP
            $otp = new EmailOtp($user->getEmail(), 'email_verification', $user, $request->getClientIp());
            $this->entityManager->persist($otp);
            $this->entityManager->flush();

            // Send OTP via email
            $emailSent = $this->emailService->sendOtpCode($otp, $user);

            if (!$emailSent) {
                $this->logger->warning('Failed to send OTP email', [
                    'user_id' => $user->getId()
                ]);
            }

            $responseData = [];

            // Only include OTP code in development environment for testing
            if ($_ENV['APP_ENV'] === 'dev') {
                $responseData['otpCode'] = $otp->getCode();
            }

            return $this->json([
                'status' => 'success',
                'message' => 'Verification code sent successfully.',
                'data' => $responseData
            ], 200);

        } catch (\Exception $e) {
            $this->logger->error('Failed to resend OTP', [
                'email' => $data['email'],
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'status' => 'error',
                'message' => 'Failed to send verification code: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * User login
     */
    #[Route('/login', name: 'login', methods: ['POST'], options: ['description' => 'Connexion utilisateur'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email']) || !isset($data['password'])) {
            $this->logFailedLogin($request, null, 'Missing credentials');
            return $this->json([
                'status' => 'error',
                'message' => 'Email and password are required'
            ], 400);
        }

        $user = $this->userRepository->findOneBy(['email' => $data['email']]);

        if (!$user) {
            $this->logFailedLogin($request, null, 'User not found');
            return $this->json([
                'status' => 'error',
                'message' => 'Invalid credentials'
            ], 401);
        }

        // Check if email is verified
        if (!$user->getIsActive()) {
            $this->logFailedLogin($request, $user, 'Email not verified');
            return $this->json([
                'status' => 'error',
                'message' => 'Please verify your email before logging in'
            ], 403);
        }

        // Verify password
        if (!$this->passwordHasher->isPasswordValid($user, $data['password'])) {
            $this->logFailedLogin($request, $user, 'Invalid password');
            return $this->json([
                'status' => 'error',
                'message' => 'Invalid credentials'
            ], 401);
        }

        try {
            // Generate JWT token
            $token = $this->jwtManager->create($user);

            // Create refresh token with IP and User-Agent for device tracking
            $refreshToken = new RefreshToken(
                $user,
                $request->getClientIp(),
                $request->headers->get('User-Agent')
            );
            $this->entityManager->persist($refreshToken);

            // Log successful login
            $this->logSuccessfulLogin($request, $user);

            $this->entityManager->flush();

            return $this->json([
                'status' => 'success',
                'message' => 'Login successful',
                'data' => [
                    'token' => $token,
                    'refreshToken' => $refreshToken->getPlainToken(), // Return plain token to client
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

        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => 'Failed to log in: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Refresh JWT token
     */
    #[Route('/refresh', name: 'refresh', methods: ['POST'], options: ['description' => 'Renouveler le token JWT'])]
    public function refresh(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['refreshToken'])) {
            return $this->json([
                'status' => 'error',
                'message' => 'Refresh token is required'
            ], 400);
        }

        // Find token by hashing the provided plain token
        $refreshToken = $this->refreshTokenRepository->findByPlainToken($data['refreshToken']);

        if (!$refreshToken) {
            return $this->json([
                'status' => 'error',
                'message' => 'Invalid refresh token'
            ], 404);
        }

        if (!$refreshToken->isValid()) {
            return $this->json([
                'status' => 'error',
                'message' => 'Refresh token has expired or been revoked'
            ], 401);
        }

        try {
            $user = $refreshToken->getUser();

            // Generate new JWT token
            $newToken = $this->jwtManager->create($user);

            // Create new refresh token (rotation) with device info
            $newRefreshToken = new RefreshToken(
                $user,
                $request->getClientIp(),
                $request->headers->get('User-Agent')
            );
            $this->entityManager->persist($newRefreshToken);

            // Revoke old refresh token
            $refreshToken->revoke();

            // Mark token as used for tracking
            $refreshToken->markAsUsed();

            $this->entityManager->flush();

            return $this->json([
                'status' => 'success',
                'message' => 'Token refreshed successfully',
                'data' => [
                    'token' => $newToken,
                    'refreshToken' => $newRefreshToken->getPlainToken(), // Return plain token
                    'expiresAt' => $newRefreshToken->getExpiresAt()->format('c')
                ]
            ], 200);

        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => 'Failed to refresh token: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Logout user
     */
    #[Route('/logout', name: 'logout', methods: ['POST'], options: ['description' => 'Déconnexion utilisateur'])]
    public function logout(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['refreshToken'])) {
            return $this->json([
                'status' => 'error',
                'message' => 'Refresh token is required'
            ], 400);
        }

        // Find token by hash
        $refreshToken = $this->refreshTokenRepository->findByPlainToken($data['refreshToken']);

        if ($refreshToken) {
            try {
                $refreshToken->revoke();
                $this->entityManager->flush();
            } catch (\Exception $e) {
                // Log error but don't fail logout
                $this->logger->warning('Failed to revoke refresh token during logout', [
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $this->json([
            'status' => 'success',
            'message' => 'Logged out successfully'
        ], 200);
    }

    /**
     * Request password reset
     */
    #[Route('/forgot-password', name: 'forgot_password', methods: ['POST'], options: ['description' => 'Demander une réinitialisation de mot de passe'])]
    public function forgotPassword(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email'])) {
            return $this->json([
                'status' => 'error',
                'message' => 'Email is required'
            ], 400);
        }

        $user = $this->userRepository->findOneBy(['email' => $data['email']]);

        // Don't reveal if user exists or not for security
        if (!$user) {
            return $this->json([
                'status' => 'success',
                'message' => 'If an account exists with this email, a password reset link will be sent.'
            ], 200);
        }

        try {
            // Invalidate old password reset tokens
            $oldTokens = $this->passwordResetTokenRepository->findBy(['user' => $user]);
            foreach ($oldTokens as $oldToken) {
                $oldToken->markAsUsed();
            }

            // Create new password reset token
            $resetToken = new PasswordResetToken($user, $request->getClientIp() ?? '');
            $this->entityManager->persist($resetToken);
            $this->entityManager->flush();

            // Send password reset email
            $this->emailService->sendPasswordResetEmail($user, $resetToken);

            $responseData = [];

            // Only include reset token in development environment for testing
            if ($_ENV['APP_ENV'] === 'dev') {
                $responseData['resetToken'] = $resetToken->getToken();
            }

            return $this->json([
                'status' => 'success',
                'message' => 'If an account exists with this email, a password reset link will be sent.',
                'data' => $responseData
            ], 200);

        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => 'Failed to process password reset request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset password
     */
    #[Route('/reset-password', name: 'reset_password', methods: ['POST'], options: ['description' => 'Réinitialiser le mot de passe'])]
    public function resetPassword(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['token']) || !isset($data['password'])) {
            return $this->json([
                'status' => 'error',
                'message' => 'Token and new password are required'
            ], 400);
        }

        $resetToken = $this->passwordResetTokenRepository->findOneBy([
            'token' => $data['token']
        ]);

        if (!$resetToken) {
            return $this->json([
                'status' => 'error',
                'message' => 'Invalid reset token'
            ], 404);
        }

        if (!$resetToken->isValid()) {
            return $this->json([
                'status' => 'error',
                'message' => 'Reset token has expired or already been used'
            ], 400);
        }

        // Validate password strength
        if (strlen($data['password']) < 8) {
            return $this->json([
                'status' => 'error',
                'message' => 'Password must be at least 8 characters long'
            ], 400);
        }

        try {
            $user = $resetToken->getUser();

            // Hash and update password
            $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);

            // Mark token as used
            $resetToken->markAsUsed();

            // Revoke all refresh tokens for security
            $this->refreshTokenRepository->revokeAllForUser($user);

            $this->entityManager->flush();

            // Send password changed notification
            $this->emailService->sendPasswordChangedNotification($user);

            return $this->json([
                'status' => 'success',
                'message' => 'Password reset successfully. You can now log in with your new password.'
            ], 200);

        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => 'Failed to reset password: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Change password (requires current password)
     */
    #[Route('/change-password', name: 'change_password', methods: ['POST'], options: ['description' => 'Changer le mot de passe (authentifié)'])]
    public function changePassword(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json([
                'status' => 'error',
                'message' => 'Not authenticated'
            ], 401);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['currentPassword']) || !isset($data['newPassword'])) {
            return $this->json([
                'status' => 'error',
                'message' => 'Current password and new password are required'
            ], 400);
        }

        // Verify current password
        if (!$this->passwordHasher->isPasswordValid($user, $data['currentPassword'])) {
            return $this->json([
                'status' => 'error',
                'message' => 'Current password is incorrect'
            ], 401);
        }

        // Validate new password strength
        if (strlen($data['newPassword']) < 8) {
            return $this->json([
                'status' => 'error',
                'message' => 'New password must be at least 8 characters long'
            ], 400);
        }

        // Check that new password is different from current
        if ($this->passwordHasher->isPasswordValid($user, $data['newPassword'])) {
            return $this->json([
                'status' => 'error',
                'message' => 'New password must be different from current password'
            ], 400);
        }

        try {
            // Hash and update password
            $hashedPassword = $this->passwordHasher->hashPassword($user, $data['newPassword']);
            $user->setPassword($hashedPassword);

            // Optionally revoke other sessions (keep current one)
            if (isset($data['logoutOtherDevices']) && $data['logoutOtherDevices'] === true) {
                $this->refreshTokenRepository->revokeAllForUser($user);
            }

            $this->entityManager->flush();

            // Send password changed notification
            $this->emailService->sendPasswordChangedNotification($user);

            return $this->json([
                'status' => 'success',
                'message' => 'Password changed successfully.'
            ], 200);

        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => 'Failed to change password: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get current user information
     */
    #[Route('/me', name: 'me', methods: ['GET'], options: ['description' => 'Obtenir les informations de l\'utilisateur connecté'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json([
                'status' => 'error',
                'message' => 'Not authenticated'
            ], 401);
        }

        return $this->json([
            'status' => 'success',
            'data' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
                'roles' => $user->getRoles(),
                'isActive' => $user->getIsActive(),
                'createdAt' => $user->getCreatedAt()?->format('c')
            ]
        ], 200, [], ['groups' => 'user:info']);
    }

    /**
     * List active sessions for current user
     */
    #[Route('/sessions', name: 'sessions_list', methods: ['GET'], options: ['description' => 'Lister les sessions actives'])]
    public function listSessions(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json([
                'status' => 'error',
                'message' => 'Not authenticated'
            ], 401);
        }

        $tokens = $this->refreshTokenRepository->findValidTokensForUser($user);

        $sessions = array_map(function (RefreshToken $token) {
            return [
                'id' => $token->getId(),
                'deviceName' => $token->getDeviceName() ?? 'Unknown Device',
                'ipAddress' => $token->getIpAddress(),
                'location' => $token->getLocation(),
                'createdAt' => $token->getCreatedAt()->format('c'),
                'lastUsedAt' => $token->getLastUsedAt()?->format('c'),
                'expiresAt' => $token->getExpiresAt()->format('c'),
            ];
        }, $tokens);

        return $this->json([
            'status' => 'success',
            'data' => $sessions,
            'total' => count($sessions)
        ], 200);
    }

    /**
     * Revoke a specific session
     */
    #[Route('/sessions/{id}', name: 'sessions_revoke', methods: ['DELETE'], options: ['description' => 'Révoquer une session spécifique'])]
    public function revokeSession(int $id): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json([
                'status' => 'error',
                'message' => 'Not authenticated'
            ], 401);
        }

        $revoked = $this->refreshTokenRepository->revokeTokenForUser($id, $user);

        if (!$revoked) {
            return $this->json([
                'status' => 'error',
                'message' => 'Session not found'
            ], 404);
        }

        return $this->json([
            'status' => 'success',
            'message' => 'Session revoked successfully'
        ], 200);
    }

    /**
     * Revoke all sessions (logout from all devices)
     */
    #[Route('/sessions', name: 'sessions_revoke_all', methods: ['DELETE'], options: ['description' => 'Révoquer toutes les sessions (déconnexion globale)'])]
    public function revokeAllSessions(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json([
                'status' => 'error',
                'message' => 'Not authenticated'
            ], 401);
        }

        $count = $this->refreshTokenRepository->revokeAllForUser($user);

        return $this->json([
            'status' => 'success',
            'message' => 'All sessions revoked successfully',
            'data' => [
                'revokedCount' => $count
            ]
        ], 200);
    }

    /**
     * Request a magic link for passwordless authentication
     */
    #[Route('/magic-link/request', name: 'magic_link_request', methods: ['POST'], options: ['description' => 'Demander un lien de connexion sans mot de passe'])]
    public function requestMagicLink(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email'])) {
            return $this->json([
                'status' => 'error',
                'message' => 'Email is required'
            ], 400);
        }

        // Rate limiting: check recent requests from this IP
        $recentFromIp = $this->magicLinkRepository->countRecentRequestsFromIp(
            $request->getClientIp() ?? 'unknown',
            15
        );

        if ($recentFromIp > 5) {
            return $this->json([
                'status' => 'error',
                'message' => 'Too many magic link requests. Please try again later.'
            ], 429);
        }

        $user = $this->userRepository->findOneBy(['email' => $data['email']]);

        // Don't reveal if user exists or not for security
        if (!$user) {
            return $this->json([
                'status' => 'success',
                'message' => 'If an account exists with this email, a login link will be sent.'
            ], 200);
        }

        // Check if user email is verified
        if (!$user->getIsActive()) {
            return $this->json([
                'status' => 'error',
                'message' => 'Please verify your email address before using magic link authentication.'
            ], 403);
        }

        // Rate limiting: check recent requests for this user
        $recentForUser = $this->magicLinkRepository->countRecentRequestsForUser($user, 15);
        if ($recentForUser > 3) {
            return $this->json([
                'status' => 'error',
                'message' => 'Too many magic link requests for this account. Please try again later.'
            ], 429);
        }

        try {
            // Invalidate any existing magic links for this user
            $this->magicLinkRepository->invalidateAllForUser($user);

            // Create new magic link
            $magicLink = new MagicLink(
                $user,
                $request->getClientIp(),
                $request->headers->get('User-Agent')
            );

            $this->entityManager->persist($magicLink);
            $this->entityManager->flush();

            // Send magic link email
            $emailSent = $this->emailService->sendMagicLinkEmail($user, $magicLink);

            if (!$emailSent) {
                $this->logger->warning('Failed to send magic link email', [
                    'user_id' => $user->getId()
                ]);
            }

            $responseData = [
                'expiresIn' => $magicLink->getTimeRemaining() . ' seconds'
            ];

            // Only include magic link token in development environment for testing
            if ($_ENV['APP_ENV'] === 'dev') {
                $responseData['magicLinkToken'] = $magicLink->getPlainToken();
            }

            return $this->json([
                'status' => 'success',
                'message' => 'If an account exists with this email, a login link will be sent.',
                'data' => $responseData
            ], 200);

        } catch (\Exception $e) {
            $this->logger->error('Failed to create magic link', [
                'email' => $data['email'],
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'status' => 'error',
                'message' => 'Failed to process magic link request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify magic link and authenticate user
     */
    #[Route('/magic-link/verify', name: 'magic_link_verify', methods: ['POST'], options: ['description' => 'Vérifier le lien magique et connecter l\'utilisateur'])]
    public function verifyMagicLink(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['token'])) {
            return $this->json([
                'status' => 'error',
                'message' => 'Magic link token is required'
            ], 400);
        }

        // Find valid magic link by token
        $magicLink = $this->magicLinkRepository->findValidByPlainToken($data['token']);

        if (!$magicLink) {
            return $this->json([
                'status' => 'error',
                'message' => 'Invalid or expired magic link'
            ], 401);
        }

        try {
            $user = $magicLink->getUser();

            // Mark magic link as used
            $magicLink->markAsUsed($request->getClientIp());

            // Generate JWT token
            $token = $this->jwtManager->create($user);

            // Create refresh token with device info
            $refreshToken = new RefreshToken(
                $user,
                $request->getClientIp(),
                $request->headers->get('User-Agent')
            );
            $this->entityManager->persist($refreshToken);

            // Log successful login via magic link
            $loginHistory = new LoginHistory(
                $user,
                $request->getClientIp() ?? 'unknown',
                $request->headers->get('User-Agent') ?? 'unknown',
                true,
                'Magic link authentication'
            );
            $this->entityManager->persist($loginHistory);

            $this->entityManager->flush();

            return $this->json([
                'status' => 'success',
                'message' => 'Magic link authentication successful',
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

        } catch (\Exception $e) {
            $this->logger->error('Magic link verification failed', [
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'status' => 'error',
                'message' => 'Failed to verify magic link: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Log successful login
     */
    private function logSuccessfulLogin(Request $request, User $user): void
    {
        $loginHistory = new LoginHistory(
            $user,
            $request->getClientIp() ?? 'unknown',
            $request->headers->get('User-Agent') ?? 'unknown',
            true
        );

        $this->entityManager->persist($loginHistory);
    }

    /**
     * Log failed login attempt
     */
    private function logFailedLogin(Request $request, ?User $user, string $reason): void
    {
        $loginHistory = new LoginHistory(
            $user,
            $request->getClientIp() ?? 'unknown',
            $request->headers->get('User-Agent') ?? 'unknown',
            false,
            $reason
        );

        $this->entityManager->persist($loginHistory);

        try {
            $this->entityManager->flush();
        } catch (\Exception $e) {
            // Silently fail - logging shouldn't break authentication
        }
    }
}
