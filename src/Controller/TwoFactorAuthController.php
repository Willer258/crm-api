<?php

namespace App\Controller;

use App\Entity\RefreshToken;
use App\Entity\User;
use App\Repository\TwoFactorAuthRepository;
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
 * Contrôleur pour l'authentification à deux facteurs (2FA TOTP)
 */
#[Route('/auth/2fa', name: 'app_auth_2fa_')]
final class TwoFactorAuthController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TwoFactorAuthRepository $twoFactorAuthRepository,
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private JWTTokenManagerInterface $jwtManager,
        private LoggerInterface $logger,
    ) {}

    /**
     * Initialise le processus de configuration 2FA
     * Génère un secret et retourne l'URL pour le QR code
     */
    #[Route('/setup', name: 'setup', methods: ['POST'], options: ['description' => 'Initialiser la configuration 2FA'])]
    public function setup(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json([
                'status' => 'error',
                'message' => 'Not authenticated'
            ], 401);
        }

        try {
            $twoFactorAuth = $this->twoFactorAuthRepository->findOrCreateForUser($user);

            // Vérifie si 2FA est déjà activé
            if ($twoFactorAuth->isEnabled()) {
                return $this->json([
                    'status' => 'error',
                    'message' => '2FA is already enabled. Disable it first to reconfigure.'
                ], 400);
            }

            // Génère un nouveau secret
            $secret = $twoFactorAuth->generateSecret();
            $qrCodeUrl = $twoFactorAuth->getQrCodeUrl($user->getEmail());

            $this->entityManager->flush();

            return $this->json([
                'status' => 'success',
                'message' => '2FA setup initialized. Scan the QR code with your authenticator app.',
                'data' => [
                    'secret' => $secret,
                    'qrCodeUrl' => $qrCodeUrl,
                    'manualEntry' => [
                        'account' => $user->getEmail(),
                        'key' => $secret,
                        'type' => 'TOTP',
                        'digits' => 6,
                        'period' => 30,
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            $this->logger->error('2FA setup failed', [
                'user' => $user->getEmail(),
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'status' => 'error',
                'message' => 'Failed to setup 2FA: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Active le 2FA après vérification du code
     */
    #[Route('/enable', name: 'enable', methods: ['POST'], options: ['description' => 'Activer le 2FA'])]
    public function enable(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json([
                'status' => 'error',
                'message' => 'Not authenticated'
            ], 401);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['code'])) {
            return $this->json([
                'status' => 'error',
                'message' => 'Verification code is required'
            ], 400);
        }

        try {
            $twoFactorAuth = $this->twoFactorAuthRepository->findOrCreateForUser($user);

            if ($twoFactorAuth->isEnabled()) {
                return $this->json([
                    'status' => 'error',
                    'message' => '2FA is already enabled'
                ], 400);
            }

            if (!$twoFactorAuth->getTotpSecret()) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Please setup 2FA first by calling /auth/2fa/setup'
                ], 400);
            }

            // Vérifie le code TOTP
            if (!$twoFactorAuth->verifyTotpCode($data['code'])) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Invalid verification code. Please try again.'
                ], 400);
            }

            // Active le 2FA et génère les codes de récupération
            $twoFactorAuth->setIsEnabled(true);
            $recoveryCodes = $twoFactorAuth->generateRecoveryCodes();

            $this->entityManager->flush();

            $this->logger->info('2FA enabled', ['user' => $user->getEmail()]);

            return $this->json([
                'status' => 'success',
                'message' => '2FA has been enabled successfully. Save your recovery codes!',
                'data' => [
                    'recoveryCodes' => $recoveryCodes,
                    'recoveryCodesCount' => \count($recoveryCodes),
                    'warning' => 'Save these recovery codes in a safe place. They will not be shown again!'
                ]
            ], 200);

        } catch (\Exception $e) {
            $this->logger->error('2FA enable failed', [
                'user' => $user->getEmail(),
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'status' => 'error',
                'message' => 'Failed to enable 2FA: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Désactive le 2FA (nécessite le mot de passe)
     */
    #[Route('/disable', name: 'disable', methods: ['POST'], options: ['description' => 'Désactiver le 2FA'])]
    public function disable(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json([
                'status' => 'error',
                'message' => 'Not authenticated'
            ], 401);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['password'])) {
            return $this->json([
                'status' => 'error',
                'message' => 'Password is required to disable 2FA'
            ], 400);
        }

        // Vérifie le mot de passe
        if (!$this->passwordHasher->isPasswordValid($user, $data['password'])) {
            return $this->json([
                'status' => 'error',
                'message' => 'Invalid password'
            ], 401);
        }

        try {
            $twoFactorAuth = $this->twoFactorAuthRepository->findOneBy(['user' => $user]);

            if (!$twoFactorAuth || !$twoFactorAuth->isEnabled()) {
                return $this->json([
                    'status' => 'error',
                    'message' => '2FA is not enabled'
                ], 400);
            }

            $twoFactorAuth->disable();
            $this->entityManager->flush();

            $this->logger->info('2FA disabled', ['user' => $user->getEmail()]);

            return $this->json([
                'status' => 'success',
                'message' => '2FA has been disabled successfully'
            ], 200);

        } catch (\Exception $e) {
            $this->logger->error('2FA disable failed', [
                'user' => $user->getEmail(),
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'status' => 'error',
                'message' => 'Failed to disable 2FA: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vérifie le code TOTP pendant le login
     * Appelé après une connexion réussie quand 2FA est activé
     */
    #[Route('/verify', name: 'verify', methods: ['POST'], options: ['description' => 'Vérifier le code 2FA lors du login'])]
    public function verify(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email']) || !isset($data['code'])) {
            return $this->json([
                'status' => 'error',
                'message' => 'Email and verification code are required'
            ], 400);
        }

        $user = $this->userRepository->findOneBy(['email' => $data['email']]);

        if (!$user) {
            return $this->json([
                'status' => 'error',
                'message' => 'Invalid credentials'
            ], 401);
        }

        $twoFactorAuth = $this->twoFactorAuthRepository->findOneBy(['user' => $user]);

        if (!$twoFactorAuth || !$twoFactorAuth->isEnabled()) {
            return $this->json([
                'status' => 'error',
                'message' => '2FA is not enabled for this account'
            ], 400);
        }

        // Vérifie le code TOTP
        if (!$twoFactorAuth->verifyTotpCode($data['code'])) {
            return $this->json([
                'status' => 'error',
                'message' => 'Invalid verification code'
            ], 401);
        }

        try {
            // Génère les tokens
            $token = $this->jwtManager->create($user);

            $refreshToken = new RefreshToken(
                $user,
                $request->getClientIp(),
                $request->headers->get('User-Agent')
            );
            $this->entityManager->persist($refreshToken);
            $this->entityManager->flush();

            return $this->json([
                'status' => 'success',
                'message' => '2FA verification successful',
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
            return $this->json([
                'status' => 'error',
                'message' => 'Failed to complete authentication: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Utilise un code de récupération pour se connecter
     */
    #[Route('/recovery', name: 'recovery', methods: ['POST'], options: ['description' => 'Utiliser un code de récupération'])]
    public function recovery(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email']) || !isset($data['recoveryCode'])) {
            return $this->json([
                'status' => 'error',
                'message' => 'Email and recovery code are required'
            ], 400);
        }

        $user = $this->userRepository->findOneBy(['email' => $data['email']]);

        if (!$user) {
            return $this->json([
                'status' => 'error',
                'message' => 'Invalid credentials'
            ], 401);
        }

        $twoFactorAuth = $this->twoFactorAuthRepository->findOneBy(['user' => $user]);

        if (!$twoFactorAuth || !$twoFactorAuth->isEnabled()) {
            return $this->json([
                'status' => 'error',
                'message' => '2FA is not enabled for this account'
            ], 400);
        }

        // Vérifie et utilise le code de récupération
        if (!$twoFactorAuth->useRecoveryCode($data['recoveryCode'])) {
            return $this->json([
                'status' => 'error',
                'message' => 'Invalid recovery code'
            ], 401);
        }

        try {
            $this->entityManager->flush();

            // Génère les tokens
            $token = $this->jwtManager->create($user);

            $refreshToken = new RefreshToken(
                $user,
                $request->getClientIp(),
                $request->headers->get('User-Agent')
            );
            $this->entityManager->persist($refreshToken);
            $this->entityManager->flush();

            $remainingCodes = $twoFactorAuth->getRecoveryCodesCount();

            $this->logger->info('2FA recovery code used', [
                'user' => $user->getEmail(),
                'remainingCodes' => $remainingCodes
            ]);

            return $this->json([
                'status' => 'success',
                'message' => 'Recovery code accepted',
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
                    ],
                    'recoveryCodesRemaining' => $remainingCodes,
                    'warning' => $remainingCodes < 3 ? 'You have only ' . $remainingCodes . ' recovery codes left. Consider regenerating them.' : null
                ]
            ], 200);

        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => 'Failed to complete authentication: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Régénère les codes de récupération
     */
    #[Route('/recovery-codes', name: 'recovery_codes', methods: ['POST'], options: ['description' => 'Régénérer les codes de récupération'])]
    public function regenerateRecoveryCodes(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json([
                'status' => 'error',
                'message' => 'Not authenticated'
            ], 401);
        }

        $data = json_decode($request->getContent(), true);

        // Nécessite le mot de passe pour régénérer les codes
        if (!isset($data['password'])) {
            return $this->json([
                'status' => 'error',
                'message' => 'Password is required'
            ], 400);
        }

        if (!$this->passwordHasher->isPasswordValid($user, $data['password'])) {
            return $this->json([
                'status' => 'error',
                'message' => 'Invalid password'
            ], 401);
        }

        try {
            $twoFactorAuth = $this->twoFactorAuthRepository->findOneBy(['user' => $user]);

            if (!$twoFactorAuth || !$twoFactorAuth->isEnabled()) {
                return $this->json([
                    'status' => 'error',
                    'message' => '2FA is not enabled'
                ], 400);
            }

            $recoveryCodes = $twoFactorAuth->generateRecoveryCodes();
            $this->entityManager->flush();

            $this->logger->info('2FA recovery codes regenerated', ['user' => $user->getEmail()]);

            return $this->json([
                'status' => 'success',
                'message' => 'Recovery codes regenerated successfully',
                'data' => [
                    'recoveryCodes' => $recoveryCodes,
                    'recoveryCodesCount' => \count($recoveryCodes),
                    'warning' => 'Save these recovery codes in a safe place. They will not be shown again!'
                ]
            ], 200);

        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => 'Failed to regenerate recovery codes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtient le statut 2FA de l'utilisateur
     */
    #[Route('/status', name: 'status', methods: ['GET'], options: ['description' => 'Obtenir le statut 2FA'])]
    public function status(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json([
                'status' => 'error',
                'message' => 'Not authenticated'
            ], 401);
        }

        $twoFactorAuth = $this->twoFactorAuthRepository->findOneBy(['user' => $user]);

        $isEnabled = $twoFactorAuth?->isEnabled() ?? false;

        return $this->json([
            'status' => 'success',
            'data' => [
                'enabled' => $isEnabled,
                'enabledAt' => $isEnabled ? $twoFactorAuth->getEnabledAt()?->format('c') : null,
                'lastUsedAt' => $isEnabled ? $twoFactorAuth->getLastUsedAt()?->format('c') : null,
                'recoveryCodesRemaining' => $isEnabled ? $twoFactorAuth->getRecoveryCodesCount() : 0,
            ]
        ], 200);
    }
}
