<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\AccountLockoutRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactory;

/**
 * Service de gestion du rate limiting pour l'authentification
 *
 * Combine le rate limiter de Symfony avec le système de verrouillage de compte
 * pour une protection complète contre les attaques par force brute.
 */
class AuthRateLimiterService
{
    public function __construct(
        private RateLimiterFactory $loginIpLimiter,
        private RateLimiterFactory $loginAccountLimiter,
        private RateLimiterFactory $passwordResetLimiter,
        private RateLimiterFactory $otpRequestLimiter,
        private RateLimiterFactory $otpVerifyLimiter,
        private RateLimiterFactory $magicLinkLimiter,
        private RateLimiterFactory $registrationLimiter,
        private AccountLockoutRepository $accountLockoutRepository,
    ) {}

    /**
     * Vérifie si une tentative de connexion est autorisée
     *
     * @return array{allowed: bool, type?: string, retryAfter?: int, lockInfo?: array}
     */
    public function checkLoginAttempt(Request $request, ?string $email = null, ?User $user = null): array
    {
        $ip = $request->getClientIp() ?? 'unknown';

        // 1. Vérifier le verrouillage de compte (si utilisateur connu)
        if ($user) {
            $lockInfo = $this->accountLockoutRepository->getLockoutInfo($user);
            if ($lockInfo) {
                return [
                    'allowed' => false,
                    'type' => 'account_locked',
                    'retryAfter' => $lockInfo['remainingSeconds'],
                    'lockInfo' => $lockInfo,
                ];
            }
        }

        // 2. Vérifier le rate limit par IP
        $ipLimiter = $this->loginIpLimiter->create($ip);
        $ipLimit = $ipLimiter->consume(0); // Peek sans consommer

        if (!$ipLimit->isAccepted()) {
            return [
                'allowed' => false,
                'type' => 'ip_rate_limit',
                'retryAfter' => $ipLimit->getRetryAfter()->getTimestamp() - time(),
            ];
        }

        // 3. Vérifier le rate limit par compte (si email fourni)
        if ($email) {
            $accountLimiter = $this->loginAccountLimiter->create($email);
            $accountLimit = $accountLimiter->consume(0); // Peek sans consommer

            if (!$accountLimit->isAccepted()) {
                return [
                    'allowed' => false,
                    'type' => 'account_rate_limit',
                    'retryAfter' => $accountLimit->getRetryAfter()->getTimestamp() - time(),
                ];
            }
        }

        return ['allowed' => true];
    }

    /**
     * Consomme un token après une tentative de connexion échouée
     */
    public function consumeLoginAttempt(Request $request, ?string $email = null, ?User $user = null): void
    {
        $ip = $request->getClientIp() ?? 'unknown';

        // Consomme un token IP
        $this->loginIpLimiter->create($ip)->consume();

        // Consomme un token compte
        if ($email) {
            $this->loginAccountLimiter->create($email)->consume();
        }

        // Enregistre l'échec dans le système de verrouillage
        if ($user) {
            $this->accountLockoutRepository->recordFailedAttempt($user, $ip);
        }
    }

    /**
     * Réinitialise les limiters après une connexion réussie
     */
    public function resetOnSuccessfulLogin(Request $request, string $email, User $user): void
    {
        $ip = $request->getClientIp() ?? 'unknown';

        // Réinitialise les limiters
        $this->loginIpLimiter->create($ip)->reset();
        $this->loginAccountLimiter->create($email)->reset();

        // Réinitialise le verrouillage de compte
        $this->accountLockoutRepository->resetOnSuccessfulLogin($user);
    }

    /**
     * Vérifie si une demande de réinitialisation de mot de passe est autorisée
     */
    public function checkPasswordResetAttempt(Request $request): array
    {
        $ip = $request->getClientIp() ?? 'unknown';

        $limiter = $this->passwordResetLimiter->create($ip);
        $limit = $limiter->consume();

        if (!$limit->isAccepted()) {
            return [
                'allowed' => false,
                'retryAfter' => $limit->getRetryAfter()->getTimestamp() - time(),
            ];
        }

        return ['allowed' => true];
    }

    /**
     * Vérifie si une demande d'OTP est autorisée
     */
    public function checkOtpRequestAttempt(Request $request): array
    {
        $ip = $request->getClientIp() ?? 'unknown';

        $limiter = $this->otpRequestLimiter->create($ip);
        $limit = $limiter->consume();

        if (!$limit->isAccepted()) {
            return [
                'allowed' => false,
                'retryAfter' => $limit->getRetryAfter()->getTimestamp() - time(),
            ];
        }

        return ['allowed' => true];
    }

    /**
     * Vérifie si une vérification d'OTP est autorisée
     */
    public function checkOtpVerifyAttempt(Request $request): array
    {
        $ip = $request->getClientIp() ?? 'unknown';

        $limiter = $this->otpVerifyLimiter->create($ip);
        $limit = $limiter->consume();

        if (!$limit->isAccepted()) {
            return [
                'allowed' => false,
                'retryAfter' => $limit->getRetryAfter()->getTimestamp() - time(),
            ];
        }

        return ['allowed' => true];
    }

    /**
     * Vérifie si une demande de magic link est autorisée
     */
    public function checkMagicLinkAttempt(Request $request): array
    {
        $ip = $request->getClientIp() ?? 'unknown';

        $limiter = $this->magicLinkLimiter->create($ip);
        $limit = $limiter->consume();

        if (!$limit->isAccepted()) {
            return [
                'allowed' => false,
                'retryAfter' => $limit->getRetryAfter()->getTimestamp() - time(),
            ];
        }

        return ['allowed' => true];
    }

    /**
     * Vérifie si une inscription est autorisée
     */
    public function checkRegistrationAttempt(Request $request): array
    {
        $ip = $request->getClientIp() ?? 'unknown';

        $limiter = $this->registrationLimiter->create($ip);
        $limit = $limiter->consume();

        if (!$limit->isAccepted()) {
            return [
                'allowed' => false,
                'retryAfter' => $limit->getRetryAfter()->getTimestamp() - time(),
            ];
        }

        return ['allowed' => true];
    }

    /**
     * Retourne le nombre de tentatives restantes pour le login
     */
    public function getLoginAttemptsRemaining(Request $request, ?string $email = null): array
    {
        $ip = $request->getClientIp() ?? 'unknown';

        $ipLimiter = $this->loginIpLimiter->create($ip);
        $ipLimit = $ipLimiter->consume(0);

        $result = [
            'ipAttemptsRemaining' => $ipLimit->getRemainingTokens(),
        ];

        if ($email) {
            $accountLimiter = $this->loginAccountLimiter->create($email);
            $accountLimit = $accountLimiter->consume(0);
            $result['accountAttemptsRemaining'] = $accountLimit->getRemainingTokens();
        }

        return $result;
    }
}
