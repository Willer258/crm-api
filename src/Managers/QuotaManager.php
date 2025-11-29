<?php

namespace App\Managers;

use App\Entity\Quota;
use App\Entity\Subscription;
use App\Entity\UsageMetric;
use App\Repository\QuotaRepository;
use App\Repository\SubscriptionRepository;
use App\Repository\UsageMetricRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class QuotaManager extends Manager
{
    public function __construct(
        EntityManagerInterface $em,
        private QuotaRepository $quotaRepository,
        private SubscriptionRepository $subscriptionRepository,
        private UsageMetricRepository $usageMetricRepository,
        private LoggerInterface $logger
    ) {
        parent::__construct($em, new \App\Utils\Sanitizer(), new \Symfony\Component\Serializer\Serializer([]));
    }

    /**
     * Initialize quotas for a subscription based on plan
     */
    public function initializeQuotasForSubscription(Subscription $subscription, string $tenantId): void
    {
        $plan = $subscription->getPlan();

        $quotaTypes = [
            Quota::TYPE_CONTACTS => $plan->getMaxContacts(),
            Quota::TYPE_COMPANIES => $plan->getMaxCompanies(),
            Quota::TYPE_DEALS => $plan->getMaxDeals(),
            Quota::TYPE_USERS => $plan->getMaxUsers(),
            Quota::TYPE_STORAGE => $plan->getMaxStorageBytes(),
            Quota::TYPE_API_CALLS => $plan->getMaxApiCallsPerDay(),
        ];

        foreach ($quotaTypes as $type => $limit) {
            $quota = $this->quotaRepository->findOneBy([
                'tenantId' => $tenantId,
                'quotaType' => $type
            ]);

            if (!$quota) {
                $quota = new Quota();
                $quota->setTenantId($tenantId);
                $quota->setQuotaType($type);
            }

            $quota->setLimitValue($limit);

            // API calls reset daily
            if ($type === Quota::TYPE_API_CALLS) {
                $quota->setResetInterval('daily');
            }

            $this->em->persist($quota);
        }

        $this->em->flush();

        $this->logger->info('Quotas initialized for subscription', [
            'subscription_id' => $subscription->getId(),
            'tenant_id' => $tenantId,
            'plan' => $plan->getName()
        ]);
    }

    /**
     * Check if tenant can perform an action
     *
     * @throws \RuntimeException if quota exceeded
     */
    public function checkQuota(string $tenantId, string $quotaType, int $requiredAmount = 1): bool
    {
        $quota = $this->getQuota($tenantId, $quotaType);

        if ($quota->isUnlimited()) {
            return true;
        }

        if (!$quota->canPerformAction($requiredAmount)) {
            if ($quota->isHardLimit()) {
                throw new \RuntimeException(sprintf(
                    'Quota exceeded for %s. Current: %d, Limit: %d',
                    $quotaType,
                    $quota->getCurrentUsage(),
                    $quota->getLimitValue()
                ));
            }

            // Soft limit - log warning but allow
            $this->logger->warning('Soft quota limit exceeded', [
                'tenant_id' => $tenantId,
                'quota_type' => $quotaType,
                'current' => $quota->getCurrentUsage(),
                'limit' => $quota->getLimitValue()
            ]);
        }

        return true;
    }

    /**
     * Increment quota usage
     */
    public function incrementUsage(string $tenantId, string $quotaType, int $amount = 1): void
    {
        $quota = $this->getQuota($tenantId, $quotaType);
        $quota->incrementUsage($amount);

        $this->em->flush();

        // Check if should notify
        if ($quota->shouldNotify()) {
            $this->notifyQuotaThreshold($tenantId, $quota);
            $quota->markAsNotified();
            $this->em->flush();
        }

        $this->logger->debug('Quota usage incremented', [
            'tenant_id' => $tenantId,
            'quota_type' => $quotaType,
            'amount' => $amount,
            'current_usage' => $quota->getCurrentUsage()
        ]);
    }

    /**
     * Decrement quota usage
     */
    public function decrementUsage(string $tenantId, string $quotaType, int $amount = 1): void
    {
        $quota = $this->getQuota($tenantId, $quotaType);
        $quota->decrementUsage($amount);

        $this->em->flush();

        $this->logger->debug('Quota usage decremented', [
            'tenant_id' => $tenantId,
            'quota_type' => $quotaType,
            'amount' => $amount,
            'current_usage' => $quota->getCurrentUsage()
        ]);
    }

    /**
     * Get or create quota for tenant
     */
    public function getQuota(string $tenantId, string $quotaType): Quota
    {
        $quota = $this->quotaRepository->findOneBy([
            'tenantId' => $tenantId,
            'quotaType' => $quotaType
        ]);

        if (!$quota) {
            // Create default quota
            $quota = new Quota();
            $quota->setTenantId($tenantId);
            $quota->setQuotaType($quotaType);
            $quota->setLimitValue(null); // Unlimited by default

            $this->em->persist($quota);
            $this->em->flush();

            $this->logger->info('Default quota created', [
                'tenant_id' => $tenantId,
                'quota_type' => $quotaType
            ]);
        }

        return $quota;
    }

    /**
     * Get all quotas for tenant
     *
     * @return Quota[]
     */
    public function getAllQuotas(string $tenantId): array
    {
        return $this->quotaRepository->findBy(['tenantId' => $tenantId]);
    }

    /**
     * Get quota usage summary
     */
    public function getUsageSummary(string $tenantId): array
    {
        $quotas = $this->getAllQuotas($tenantId);
        $summary = [];

        foreach ($quotas as $quota) {
            $summary[$quota->getQuotaType()] = [
                'current' => $quota->getCurrentUsage(),
                'limit' => $quota->getLimitValue(),
                'percentage' => $quota->getUsagePercentage(),
                'remaining' => $quota->getRemainingQuota(),
                'is_exceeded' => $quota->isExceeded(),
                'is_approaching_limit' => $quota->isApproachingThreshold(),
                'formatted_current' => $quota->getFormattedUsage(),
                'formatted_limit' => $quota->getFormattedLimit(),
            ];
        }

        return $summary;
    }

    /**
     * Reset quotas based on reset interval
     */
    public function processQuotaResets(): array
    {
        $stats = [
            'daily_resets' => 0,
            'monthly_resets' => 0,
        ];

        // Reset daily quotas
        $dailyQuotas = $this->quotaRepository->findBy(['resetInterval' => 'daily']);
        foreach ($dailyQuotas as $quota) {
            if ($this->shouldReset($quota, 'daily')) {
                $quota->resetUsage();
                $stats['daily_resets']++;
            }
        }

        // Reset monthly quotas
        $monthlyQuotas = $this->quotaRepository->findBy(['resetInterval' => 'monthly']);
        foreach ($monthlyQuotas as $quota) {
            if ($this->shouldReset($quota, 'monthly')) {
                $quota->resetUsage();
                $stats['monthly_resets']++;
            }
        }

        $this->em->flush();

        $this->logger->info('Quota resets processed', $stats);

        return $stats;
    }

    /**
     * Check if quota should be reset
     */
    private function shouldReset(Quota $quota, string $interval): bool
    {
        $lastReset = $quota->getLastResetAt();

        if ($lastReset === null) {
            return true; // Never reset before
        }

        $now = new \DateTimeImmutable();

        return match($interval) {
            'daily' => $lastReset->format('Y-m-d') !== $now->format('Y-m-d'),
            'monthly' => $lastReset->format('Y-m') !== $now->format('Y-m'),
            default => false
        };
    }

    /**
     * Synchronize current usage from database
     */
    public function synchronizeUsage(string $tenantId): void
    {
        // Get active subscription
        $subscription = $this->getActiveSubscription($tenantId);

        if (!$subscription) {
            $this->logger->warning('No active subscription found for tenant', [
                'tenant_id' => $tenantId
            ]);
            return;
        }

        // Count actual usage
        $usage = $this->countTenantUsage($tenantId);

        // Update quotas
        foreach ($usage as $type => $count) {
            $quota = $this->getQuota($tenantId, $type);
            $quota->setCurrentUsage($count);
        }

        $this->em->flush();

        $this->logger->info('Usage synchronized', [
            'tenant_id' => $tenantId,
            'usage' => $usage
        ]);
    }

    /**
     * Count actual usage from database
     */
    private function countTenantUsage(string $tenantId): array
    {
        // TODO: Implement actual counting based on tenant data
        // This would query Contact, Company, Deal, User tables
        // For now, return placeholder

        return [
            Quota::TYPE_CONTACTS => 0,
            Quota::TYPE_COMPANIES => 0,
            Quota::TYPE_DEALS => 0,
            Quota::TYPE_USERS => 0,
            Quota::TYPE_STORAGE => 0,
            Quota::TYPE_API_CALLS => 0,
        ];
    }

    /**
     * Get active subscription for tenant
     */
    private function getActiveSubscription(string $tenantId): ?Subscription
    {
        return $this->subscriptionRepository->findOneBy([
            'tenantId' => $tenantId,
            'status' => [
                Subscription::STATUS_ACTIVE,
                Subscription::STATUS_TRIALING
            ]
        ]);
    }

    /**
     * Notify about quota threshold
     */
    private function notifyQuotaThreshold(string $tenantId, Quota $quota): void
    {
        // TODO: Send notification via email or webhook

        $this->logger->warning('Quota threshold reached', [
            'tenant_id' => $tenantId,
            'quota_type' => $quota->getQuotaType(),
            'usage_percentage' => $quota->getUsagePercentage(),
            'current' => $quota->getCurrentUsage(),
            'limit' => $quota->getLimitValue()
        ]);
    }

    /**
     * Check if specific action is allowed
     */
    public function canCreateContact(string $tenantId): bool
    {
        try {
            return $this->checkQuota($tenantId, Quota::TYPE_CONTACTS);
        } catch (\RuntimeException $e) {
            return false;
        }
    }

    public function canCreateCompany(string $tenantId): bool
    {
        try {
            return $this->checkQuota($tenantId, Quota::TYPE_COMPANIES);
        } catch (\RuntimeException $e) {
            return false;
        }
    }

    public function canCreateDeal(string $tenantId): bool
    {
        try {
            return $this->checkQuota($tenantId, Quota::TYPE_DEALS);
        } catch (\RuntimeException $e) {
            return false;
        }
    }

    public function canCreateUser(string $tenantId): bool
    {
        try {
            return $this->checkQuota($tenantId, Quota::TYPE_USERS);
        } catch (\RuntimeException $e) {
            return false;
        }
    }

    public function canUploadFile(string $tenantId, int $fileSize): bool
    {
        try {
            $quota = $this->getQuota($tenantId, Quota::TYPE_STORAGE);

            if ($quota->isUnlimited()) {
                return true;
            }

            $newTotal = $quota->getCurrentUsage() + $fileSize;
            return $newTotal <= $quota->getLimitValue();
        } catch (\RuntimeException $e) {
            return false;
        }
    }

    public function canMakeApiCall(string $tenantId): bool
    {
        try {
            return $this->checkQuota($tenantId, Quota::TYPE_API_CALLS);
        } catch (\RuntimeException $e) {
            return false;
        }
    }

    /**
     * Record file upload in storage quota
     */
    public function recordFileUpload(string $tenantId, int $fileSize): void
    {
        $this->incrementUsage($tenantId, Quota::TYPE_STORAGE, $fileSize);
    }

    /**
     * Record file deletion in storage quota
     */
    public function recordFileDeletion(string $tenantId, int $fileSize): void
    {
        $this->decrementUsage($tenantId, Quota::TYPE_STORAGE, $fileSize);
    }

    /**
     * Record API call
     */
    public function recordApiCall(string $tenantId): void
    {
        $this->incrementUsage($tenantId, Quota::TYPE_API_CALLS);
    }
}
