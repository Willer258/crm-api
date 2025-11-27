<?php

namespace App\Managers;

use App\Entity\EmailVerificationToken;
use App\Entity\LoginHistory;
use App\Entity\PasswordResetToken;
use App\Entity\RefreshToken;
use App\Entity\User;
use App\Repository\EmailVerificationTokenRepository;
use App\Repository\LoginHistoryRepository;
use App\Repository\PasswordResetTokenRepository;
use App\Repository\RefreshTokenRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthManager extends Manager
{
    // Security constants
    private const MAX_LOGIN_ATTEMPTS_PER_USER = 5;
    private const MAX_LOGIN_ATTEMPTS_PER_IP = 10;
    private const LOGIN_ATTEMPT_WINDOW_MINUTES = 15;
    private const MAX_PASSWORD_RESET_ATTEMPTS = 3;
    private const PASSWORD_RESET_WINDOW_MINUTES = 60;
    private const MIN_PASSWORD_LENGTH = 8;

    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepository,
        private RefreshTokenRepository $refreshTokenRepository,
        private EmailVerificationTokenRepository $emailVerificationTokenRepository,
        private PasswordResetTokenRepository $passwordResetTokenRepository,
        private LoginHistoryRepository $loginHistoryRepository,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        $this->em = $em;
    }

    /**
     * Validate user credentials and check rate limiting
     *
     * @throws \RuntimeException if too many failed attempts
     */
    public function validateLoginAttempt(string $email, string $ipAddress): void
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);

        // Check failed attempts per user
        if ($user) {
            $userAttempts = $this->loginHistoryRepository->countFailedAttemptsForUser(
                $user,
                self::LOGIN_ATTEMPT_WINDOW_MINUTES
            );

            if ($userAttempts >= self::MAX_LOGIN_ATTEMPTS_PER_USER) {
                throw new \RuntimeException(
                    'Too many failed login attempts. Please try again later or reset your password.'
                );
            }
        }

        // Check failed attempts per IP
        $ipAttempts = $this->loginHistoryRepository->countFailedAttemptsByIp(
            $ipAddress,
            self::LOGIN_ATTEMPT_WINDOW_MINUTES
        );

        if ($ipAttempts >= self::MAX_LOGIN_ATTEMPTS_PER_IP) {
            throw new \RuntimeException(
                'Too many failed login attempts from this IP address. Please try again later.'
            );
        }
    }

    /**
     * Validate password reset rate limiting
     *
     * @throws \RuntimeException if too many attempts
     */
    public function validatePasswordResetAttempt(User $user): void
    {
        $attempts = $this->passwordResetTokenRepository->countRecentAttemptsForUser(
            $user,
            self::PASSWORD_RESET_WINDOW_MINUTES
        );

        if ($attempts >= self::MAX_PASSWORD_RESET_ATTEMPTS) {
            throw new \RuntimeException(
                'Too many password reset requests. Please try again later.'
            );
        }
    }

    /**
     * Validate password strength
     *
     * @return array Array of validation errors (empty if valid)
     */
    public function validatePasswordStrength(string $password): array
    {
        $errors = [];

        if (strlen($password) < self::MIN_PASSWORD_LENGTH) {
            $errors[] = sprintf('Password must be at least %d characters long', self::MIN_PASSWORD_LENGTH);
        }

        // Check for at least one uppercase letter
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }

        // Check for at least one lowercase letter
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }

        // Check for at least one number
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }

        // Check for at least one special character
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }

        // Check for common passwords
        $commonPasswords = [
            'password', 'password123', '12345678', 'qwerty', 'abc123',
            'password1', '123456789', 'letmein', 'welcome', 'admin'
        ];

        if (in_array(strtolower($password), $commonPasswords)) {
            $errors[] = 'This password is too common. Please choose a more secure password';
        }

        return $errors;
    }

    /**
     * Register a new user
     *
     * @throws \InvalidArgumentException on validation errors
     * @throws \RuntimeException if user already exists
     */
    public function registerUser(array $data): User
    {
        // Validate required fields
        if (empty($data['email'])) {
            throw new \InvalidArgumentException('Email is required');
        }

        if (empty($data['password'])) {
            throw new \InvalidArgumentException('Password is required');
        }

        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email format');
        }

        // Check if user exists
        $existingUser = $this->userRepository->findOneBy(['email' => $data['email']]);
        if ($existingUser) {
            throw new \RuntimeException('User with this email already exists');
        }

        // Validate password strength
        $passwordErrors = $this->validatePasswordStrength($data['password']);
        if (!empty($passwordErrors)) {
            throw new \InvalidArgumentException(implode('. ', $passwordErrors));
        }

        // Create user
        $user = new User([
            'email' => $data['email'],
            'firstname' => $data['firstname'] ?? '',
            'lastname' => $data['lastname'] ?? '',
            'password' => '', // Will be set below
            'roles' => $data['roles'] ?? ['ROLE_USER'],
            'isActive' => false, // Requires email verification
        ]);

        // Hash password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    /**
     * Create email verification token
     */
    public function createEmailVerificationToken(User $user): EmailVerificationToken
    {
        // Invalidate old tokens
        $this->emailVerificationTokenRepository->markAllAsUsedForUser($user);

        $token = new EmailVerificationToken($user);
        $this->em->persist($token);
        $this->em->flush();

        return $token;
    }

    /**
     * Verify email with token
     *
     * @throws \RuntimeException if token is invalid
     */
    public function verifyEmail(string $tokenString): User
    {
        $token = $this->emailVerificationTokenRepository->findOneBy(['token' => $tokenString]);

        if (!$token) {
            throw new \RuntimeException('Invalid verification token');
        }

        if (!$token->isValid()) {
            throw new \RuntimeException('Verification token has expired or already been used');
        }

        $user = $token->getUser();
        $user->setIsActive(true);
        $token->markAsUsed();

        $this->em->flush();

        return $user;
    }

    /**
     * Create password reset token
     *
     * @throws \RuntimeException if rate limit exceeded
     */
    public function createPasswordResetToken(User $user, string $ipAddress): PasswordResetToken
    {
        // Check rate limiting
        $this->validatePasswordResetAttempt($user);

        // Invalidate old tokens
        $this->passwordResetTokenRepository->markAllAsUsedForUser($user);

        $token = new PasswordResetToken($user, $ipAddress);
        $this->em->persist($token);
        $this->em->flush();

        return $token;
    }

    /**
     * Reset password with token
     *
     * @throws \RuntimeException if token is invalid
     * @throws \InvalidArgumentException if password is weak
     */
    public function resetPassword(string $tokenString, string $newPassword): User
    {
        $token = $this->passwordResetTokenRepository->findOneBy(['token' => $tokenString]);

        if (!$token) {
            throw new \RuntimeException('Invalid reset token');
        }

        if (!$token->isValid()) {
            throw new \RuntimeException('Reset token has expired or already been used');
        }

        // Validate password strength
        $passwordErrors = $this->validatePasswordStrength($newPassword);
        if (!empty($passwordErrors)) {
            throw new \InvalidArgumentException(implode('. ', $passwordErrors));
        }

        $user = $token->getUser();

        // Hash and update password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);

        // Mark token as used
        $token->markAsUsed();

        // Revoke all refresh tokens for security
        $this->refreshTokenRepository->revokeAllForUser($user);

        $this->em->flush();

        return $user;
    }

    /**
     * Create refresh token for user
     */
    public function createRefreshToken(User $user): RefreshToken
    {
        $token = new RefreshToken($user);
        $this->em->persist($token);
        $this->em->flush();

        return $token;
    }

    /**
     * Validate refresh token
     *
     * @throws \RuntimeException if token is invalid
     */
    public function validateRefreshToken(string $tokenString): RefreshToken
    {
        $token = $this->refreshTokenRepository->findOneBy(['token' => $tokenString]);

        if (!$token) {
            throw new \RuntimeException('Invalid refresh token');
        }

        if (!$token->isValid()) {
            throw new \RuntimeException('Refresh token has expired or been revoked');
        }

        return $token;
    }

    /**
     * Revoke refresh token
     */
    public function revokeRefreshToken(string $tokenString): void
    {
        $token = $this->refreshTokenRepository->findOneBy(['token' => $tokenString]);

        if ($token) {
            $token->revoke();
            $this->em->flush();
        }
    }

    /**
     * Revoke all refresh tokens for a user
     */
    public function revokeAllRefreshTokens(User $user): void
    {
        $this->refreshTokenRepository->revokeAllForUser($user);
    }

    /**
     * Log successful login
     */
    public function logSuccessfulLogin(User $user, string $ipAddress, string $userAgent): void
    {
        $loginHistory = new LoginHistory($user, $ipAddress, $userAgent, true);
        $this->em->persist($loginHistory);
        $this->em->flush();
    }

    /**
     * Log failed login attempt
     */
    public function logFailedLogin(?User $user, string $ipAddress, string $userAgent, string $reason): void
    {
        $loginHistory = new LoginHistory($user, $ipAddress, $userAgent, false, $reason);
        $this->em->persist($loginHistory);
        $this->em->flush();
    }

    /**
     * Get login history for a user
     *
     * @return LoginHistory[]
     */
    public function getLoginHistory(User $user, int $limit = 10): array
    {
        return $this->loginHistoryRepository->findByUser($user, $limit);
    }

    /**
     * Get recent successful logins for a user
     *
     * @return LoginHistory[]
     */
    public function getRecentSuccessfulLogins(User $user, int $limit = 5): array
    {
        return $this->loginHistoryRepository->findRecentSuccessfulLogins($user, $limit);
    }

    /**
     * Clean up expired tokens (should be run periodically via cron)
     */
    public function cleanupExpiredTokens(): array
    {
        $stats = [
            'refreshTokens' => 0,
            'emailVerificationTokens' => 0,
            'passwordResetTokens' => 0,
            'loginHistory' => 0,
        ];

        // Delete expired refresh tokens
        $stats['refreshTokens'] = $this->refreshTokenRepository->deleteExpired();

        // Delete expired email verification tokens
        $stats['emailVerificationTokens'] = $this->emailVerificationTokenRepository->deleteExpired();

        // Delete expired password reset tokens
        $stats['passwordResetTokens'] = $this->passwordResetTokenRepository->deleteExpired();

        // Delete login history older than 90 days
        $ninetyDaysAgo = new \DateTimeImmutable('-90 days');
        $stats['loginHistory'] = $this->loginHistoryRepository->deleteOlderThan($ninetyDaysAgo);

        return $stats;
    }

    /**
     * Check if user account is locked due to too many failed attempts
     */
    public function isAccountLocked(User $user): bool
    {
        $attempts = $this->loginHistoryRepository->countFailedAttemptsForUser(
            $user,
            self::LOGIN_ATTEMPT_WINDOW_MINUTES
        );

        return $attempts >= self::MAX_LOGIN_ATTEMPTS_PER_USER;
    }

    /**
     * Check if IP is blocked due to too many failed attempts
     */
    public function isIpBlocked(string $ipAddress): bool
    {
        $attempts = $this->loginHistoryRepository->countFailedAttemptsByIp(
            $ipAddress,
            self::LOGIN_ATTEMPT_WINDOW_MINUTES
        );

        return $attempts >= self::MAX_LOGIN_ATTEMPTS_PER_IP;
    }

    /**
     * Get suspicious activity report
     */
    public function getSuspiciousActivity(int $minutes = 60, int $threshold = 5): array
    {
        return $this->loginHistoryRepository->findSuspiciousActivity($minutes, $threshold);
    }
}
