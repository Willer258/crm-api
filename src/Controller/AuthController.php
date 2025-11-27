<?php

namespace App\Controller;

use App\Entity\EmailVerificationToken;
use App\Entity\LoginHistory;
use App\Entity\PasswordResetToken;
use App\Entity\RefreshToken;
use App\Entity\User;
use App\Repository\EmailVerificationTokenRepository;
use App\Repository\PasswordResetTokenRepository;
use App\Repository\RefreshTokenRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

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
        private EmailVerificationTokenRepository $emailVerificationTokenRepository,
        private PasswordResetTokenRepository $passwordResetTokenRepository,
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
            // Create new user
            $user = new User([
                'email' => $data['email'],
                'firstname' => $data['firstname'] ?? '',
                'lastname' => $data['lastname'] ?? '',
                'password' => '', // Will be set below
                'roles' => ['ROLE_USER'],
                'isActive' => false, // User must verify email first
            ]);

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

            // Create email verification token
            $verificationToken = new EmailVerificationToken($user);
            $this->entityManager->persist($verificationToken);

            $this->entityManager->flush();

            // TODO: Send verification email via EmailService
            // $this->emailService->sendVerificationEmail($user, $verificationToken);

            return $this->json([
                'status' => 'success',
                'message' => 'User registered successfully. Please check your email to verify your account.',
                'data' => [
                    'userId' => $user->getId(),
                    'email' => $user->getEmail(),
                    // TODO: Remove in production - only for testing
                    'verificationToken' => $verificationToken->getToken()
                ]
            ], 201);

        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => 'Failed to register user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify email address
     */
    #[Route('/verify-email', name: 'verify_email', methods: ['POST'], options: ['description' => 'Vérifier l\'adresse email'])]
    public function verifyEmail(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['token'])) {
            return $this->json([
                'status' => 'error',
                'message' => 'Verification token is required'
            ], 400);
        }

        $verificationToken = $this->emailVerificationTokenRepository->findOneBy([
            'token' => $data['token']
        ]);

        if (!$verificationToken) {
            return $this->json([
                'status' => 'error',
                'message' => 'Invalid verification token'
            ], 404);
        }

        if (!$verificationToken->isValid()) {
            return $this->json([
                'status' => 'error',
                'message' => 'Verification token has expired or already been used'
            ], 400);
        }

        try {
            $user = $verificationToken->getUser();
            $user->setIsActive(true);
            $verificationToken->markAsUsed();

            $this->entityManager->flush();

            return $this->json([
                'status' => 'success',
                'message' => 'Email verified successfully. You can now log in.',
                'data' => [
                    'userId' => $user->getId(),
                    'email' => $user->getEmail()
                ]
            ], 200);

        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => 'Failed to verify email: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Resend email verification
     */
    #[Route('/resend-verification', name: 'resend_verification', methods: ['POST'], options: ['description' => 'Renvoyer l\'email de vérification'])]
    public function resendVerification(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email'])) {
            return $this->json([
                'status' => 'error',
                'message' => 'Email is required'
            ], 400);
        }

        $user = $this->userRepository->findOneBy(['email' => $data['email']]);

        if (!$user) {
            // Don't reveal if user exists or not for security
            return $this->json([
                'status' => 'success',
                'message' => 'If an account exists with this email, a verification email will be sent.'
            ], 200);
        }

        if ($user->getIsActive()) {
            return $this->json([
                'status' => 'error',
                'message' => 'Email is already verified'
            ], 400);
        }

        try {
            // Invalidate old tokens
            $oldTokens = $this->emailVerificationTokenRepository->findBy(['user' => $user]);
            foreach ($oldTokens as $oldToken) {
                $oldToken->markAsUsed();
            }

            // Create new verification token
            $verificationToken = new EmailVerificationToken($user);
            $this->entityManager->persist($verificationToken);
            $this->entityManager->flush();

            // TODO: Send verification email via EmailService
            // $this->emailService->sendVerificationEmail($user, $verificationToken);

            return $this->json([
                'status' => 'success',
                'message' => 'Verification email sent successfully.',
                'data' => [
                    // TODO: Remove in production - only for testing
                    'verificationToken' => $verificationToken->getToken()
                ]
            ], 200);

        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => 'Failed to send verification email: ' . $e->getMessage()
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

            // Create refresh token
            $refreshToken = new RefreshToken($user);
            $this->entityManager->persist($refreshToken);

            // Log successful login
            $this->logSuccessfulLogin($request, $user);

            $this->entityManager->flush();

            return $this->json([
                'status' => 'success',
                'message' => 'Login successful',
                'data' => [
                    'token' => $token,
                    'refreshToken' => $refreshToken->getToken(),
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

        $refreshToken = $this->refreshTokenRepository->findOneBy([
            'token' => $data['refreshToken']
        ]);

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

            // Optionally: Create new refresh token (rotation)
            $newRefreshToken = new RefreshToken($user);
            $this->entityManager->persist($newRefreshToken);

            // Revoke old refresh token
            $refreshToken->revoke();

            $this->entityManager->flush();

            return $this->json([
                'status' => 'success',
                'message' => 'Token refreshed successfully',
                'data' => [
                    'token' => $newToken,
                    'refreshToken' => $newRefreshToken->getToken(),
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

        $refreshToken = $this->refreshTokenRepository->findOneBy([
            'token' => $data['refreshToken']
        ]);

        if ($refreshToken) {
            try {
                $refreshToken->revoke();
                $this->entityManager->flush();
            } catch (\Exception $e) {
                // Log error but don't fail logout
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

            // TODO: Send password reset email via EmailService
            // $this->emailService->sendPasswordResetEmail($user, $resetToken);

            return $this->json([
                'status' => 'success',
                'message' => 'If an account exists with this email, a password reset link will be sent.',
                'data' => [
                    // TODO: Remove in production - only for testing
                    'resetToken' => $resetToken->getToken()
                ]
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
            $refreshTokens = $this->refreshTokenRepository->findBy(['user' => $user]);
            foreach ($refreshTokens as $refreshToken) {
                $refreshToken->revoke();
            }

            $this->entityManager->flush();

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
