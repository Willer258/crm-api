<?php

namespace App\Service;

use App\Entity\Subscription;
use App\Repository\SubscriptionRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Super Admin Service - Global monitoring and management
 *
 * Provides comprehensive view of:
 * - All tenants status
 * - Platform health
 * - Revenue metrics
 * - Support analytics
 * - System performance
 */
class SuperAdminService
{
    public function __construct(
        private EntityManagerInterface $em,
        private Connection $connection,
        private SubscriptionRepository $subscriptionRepository,
        private SaasMetricsService $metricsService,
        private LoggerInterface $logger
    ) {}

    /**
     * Get comprehensive platform overview
     */
    public function getPlatformOverview(): array
    {
        $this->logger->info('Generating platform overview');

        return [
            'summary' => $this->getPlatformSummary(),
            'tenants' => $this->getTenantsOverview(),
            'revenue' => $this->getRevenueMetrics(),
            'health' => $this->getSystemHealth(),
            'growth' => $this->getGrowthMetrics(),
            'generated_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s')
        ];
    }

    /**
     * Get platform summary statistics
     */
    private function getPlatformSummary(): array
    {
        $now = new \DateTimeImmutable();
        $lastMonth = $now->modify('-1 month');

        // Total tenants
        $totalTenants = $this->getTotalTenants();

        // Active subscriptions
        $activeSubscriptions = $this->countByStatus([
            Subscription::STATUS_ACTIVE,
            Subscription::STATUS_TRIALING
        ]);

        // New tenants this month
        $newTenantsThisMonth = $this->countNewTenants($lastMonth, $now);

        // Churned tenants this month
        $churnedThisMonth = $this->countChurnedTenants($lastMonth, $now);

        return [
            'total_tenants' => $totalTenants,
            'active_subscriptions' => $activeSubscriptions,
            'trialing_subscriptions' => $this->countByStatus([Subscription::STATUS_TRIALING]),
            'past_due_subscriptions' => $this->countByStatus([Subscription::STATUS_PAST_DUE]),
            'canceled_subscriptions' => $this->countByStatus([Subscription::STATUS_CANCELED]),
            'new_tenants_this_month' => $newTenantsThisMonth,
            'churned_this_month' => $churnedThisMonth,
            'activation_rate' => $this->calculateActivationRate(),
        ];
    }

    /**
     * Get detailed tenants overview
     */
    public function getTenantsOverview(array $filters = []): array
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('s', 'p')
           ->from(Subscription::class, 's')
           ->join('s.plan', 'p')
           ->orderBy('s.createdAt', 'DESC');

        // Apply filters
        if (!empty($filters['status'])) {
            $qb->andWhere('s.status = :status')
               ->setParameter('status', $filters['status']);
        }

        if (!empty($filters['plan_code'])) {
            $qb->andWhere('p.code = :plan_code')
               ->setParameter('plan_code', $filters['plan_code']);
        }

        if (!empty($filters['search'])) {
            $qb->andWhere('s.tenantId LIKE :search')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        $subscriptions = $qb->getQuery()->getResult();

        return array_map(function($subscription) {
            return $this->formatTenantInfo($subscription);
        }, $subscriptions);
    }

    /**
     * Get detailed info for a specific tenant
     */
    public function getTenantDetails(string $tenantId): array
    {
        $subscription = $this->subscriptionRepository->findOneBy(['tenantId' => $tenantId]);

        if (!$subscription) {
            throw new \InvalidArgumentException("Tenant not found: {$tenantId}");
        }

        return [
            'tenant_info' => $this->formatTenantInfo($subscription),
            'database_info' => $this->getTenantDatabaseInfo($tenantId),
            'usage_metrics' => $this->getTenantUsageMetrics($tenantId),
            'billing_history' => $this->getTenantBillingHistory($subscription),
            'activity_log' => $this->getTenantActivityLog($tenantId),
            'support_tickets' => $this->getTenantSupportTickets($tenantId),
        ];
    }

    /**
     * Format tenant information
     */
    private function formatTenantInfo(Subscription $subscription): array
    {
        $plan = $subscription->getPlan();
        $now = new \DateTimeImmutable();

        return [
            'tenant_id' => $subscription->getTenantId(),
            'subscription_id' => $subscription->getId(),
            'status' => $subscription->getStatus(),
            'plan' => [
                'code' => $plan->getCode(),
                'name' => $plan->getName(),
                'price' => $plan->getPrice(),
                'currency' => $plan->getCurrency(),
                'billing_interval' => $plan->getBillingInterval()
            ],
            'dates' => [
                'created_at' => $subscription->getCreatedAt()->format('Y-m-d H:i:s'),
                'start_date' => $subscription->getStartDate()->format('Y-m-d H:i:s'),
                'current_period_start' => $subscription->getCurrentPeriodStart()?->format('Y-m-d H:i:s'),
                'current_period_end' => $subscription->getCurrentPeriodEnd()?->format('Y-m-d H:i:s'),
                'trial_ends_at' => $subscription->getTrialEndsAt()?->format('Y-m-d H:i:s'),
                'canceled_at' => $subscription->getCanceledAt()?->format('Y-m-d H:i:s'),
            ],
            'age_days' => $subscription->getCreatedAt()->diff($now)->days,
            'is_trial' => $subscription->isInTrial(),
            'is_active' => $subscription->isActive(),
            'is_past_due' => $subscription->isPastDue(),
            'mrr_contribution' => $this->calculateMRRContribution($subscription),
            'stripe' => [
                'customer_id' => $subscription->getStripeCustomerId(),
                'subscription_id' => $subscription->getStripeSubscriptionId()
            ]
        ];
    }

    /**
     * Get tenant database information
     */
    private function getTenantDatabaseInfo(string $tenantId): array
    {
        try {
            // Get database size
            $params = $this->connection->getParams();
            $dbName = 'wiassur_' . $params['dbname']; // Adjust based on zone

            $sql = "SELECT
                    table_schema AS 'database_name',
                    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'size_mb',
                    COUNT(*) AS 'table_count'
                FROM information_schema.tables
                WHERE table_schema = :dbname
                GROUP BY table_schema";

            $stmt = $this->connection->prepare($sql);
            $result = $stmt->executeQuery(['dbname' => $dbName]);
            $data = $result->fetchAssociative();

            return [
                'database_name' => $dbName,
                'size_mb' => $data['size_mb'] ?? 0,
                'table_count' => $data['table_count'] ?? 0,
                'exists' => !empty($data)
            ];

        } catch (\Exception $e) {
            $this->logger->error('Failed to get database info', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage()
            ]);

            return [
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get tenant usage metrics
     */
    private function getTenantUsageMetrics(string $tenantId): array
    {
        // TODO: Implement actual usage tracking
        // This would query tenant-specific data (contacts, deals, etc.)

        return [
            'contacts_count' => 0,
            'companies_count' => 0,
            'deals_count' => 0,
            'users_count' => 0,
            'storage_used_mb' => 0,
            'api_calls_today' => 0,
        ];
    }

    /**
     * Get tenant billing history
     */
    private function getTenantBillingHistory(Subscription $subscription): array
    {
        $invoices = $subscription->getInvoices();

        return array_map(function($invoice) {
            return [
                'invoice_number' => $invoice->getInvoiceNumber(),
                'date' => $invoice->getInvoiceDate()->format('Y-m-d'),
                'amount' => $invoice->getTotal(),
                'currency' => $invoice->getCurrency(),
                'status' => $invoice->getStatus(),
                'paid_at' => $invoice->getPaidAt()?->format('Y-m-d H:i:s')
            ];
        }, $invoices->toArray());
    }

    /**
     * Get tenant activity log
     */
    private function getTenantActivityLog(string $tenantId, int $limit = 50): array
    {
        // TODO: Implement audit log system
        return [];
    }

    /**
     * Get tenant support tickets
     */
    private function getTenantSupportTickets(string $tenantId): array
    {
        // TODO: Implement support ticket system
        return [];
    }

    /**
     * Get revenue metrics
     */
    private function getRevenueMetrics(): array
    {
        $mrrData = $this->metricsService->calculateMRR();
        $arrData = $this->metricsService->calculateARR();

        return [
            'mrr' => $mrrData,
            'arr' => $arrData,
            'trends' => $this->metricsService->getRevenueTrends(12)
        ];
    }

    /**
     * Get system health metrics
     */
    public function getSystemHealth(): array
    {
        return [
            'database' => $this->checkDatabaseHealth(),
            'storage' => $this->checkStorageHealth(),
            'api_performance' => $this->checkAPIPerformance(),
            'background_jobs' => $this->checkBackgroundJobs(),
            'overall_status' => 'healthy' // Can be: healthy, degraded, critical
        ];
    }

    /**
     * Check database health
     */
    private function checkDatabaseHealth(): array
    {
        try {
            $this->connection->executeQuery('SELECT 1');

            return [
                'status' => 'healthy',
                'response_time_ms' => 0, // TODO: Measure actual response time
                'connections' => $this->getDatabaseConnections()
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get database connections count
     */
    private function getDatabaseConnections(): int
    {
        try {
            $sql = "SHOW STATUS WHERE Variable_name = 'Threads_connected'";
            $stmt = $this->connection->executeQuery($sql);
            $result = $stmt->fetchAssociative();

            return (int) ($result['Value'] ?? 0);

        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Check storage health
     */
    private function checkStorageHealth(): array
    {
        $uploadDir = __DIR__ . '/../../var/uploads';

        if (!is_dir($uploadDir)) {
            return ['status' => 'unknown'];
        }

        $totalSpace = disk_total_space($uploadDir);
        $freeSpace = disk_free_space($uploadDir);
        $usedSpace = $totalSpace - $freeSpace;
        $usagePercent = ($usedSpace / $totalSpace) * 100;

        return [
            'status' => $usagePercent > 90 ? 'warning' : 'healthy',
            'total_gb' => round($totalSpace / 1024 / 1024 / 1024, 2),
            'used_gb' => round($usedSpace / 1024 / 1024 / 1024, 2),
            'free_gb' => round($freeSpace / 1024 / 1024 / 1024, 2),
            'usage_percent' => round($usagePercent, 2)
        ];
    }

    /**
     * Check API performance
     */
    private function checkAPIPerformance(): array
    {
        // TODO: Implement actual performance monitoring
        return [
            'status' => 'healthy',
            'avg_response_time_ms' => 150,
            'requests_per_minute' => 0,
            'error_rate_percent' => 0
        ];
    }

    /**
     * Check background jobs
     */
    private function checkBackgroundJobs(): array
    {
        // TODO: Check queue/scheduler status
        return [
            'status' => 'healthy',
            'pending_jobs' => 0,
            'failed_jobs' => 0
        ];
    }

    /**
     * Get growth metrics
     */
    private function getGrowthMetrics(): array
    {
        $now = new \DateTimeImmutable();
        $lastMonth = $now->modify('-1 month');
        $twoMonthsAgo = $now->modify('-2 months');

        $thisMonthNew = $this->countNewTenants($lastMonth, $now);
        $lastMonthNew = $this->countNewTenants($twoMonthsAgo, $lastMonth);

        $growthRate = $lastMonthNew > 0
            ? (($thisMonthNew - $lastMonthNew) / $lastMonthNew) * 100
            : 0;

        return [
            'new_tenants_this_month' => $thisMonthNew,
            'new_tenants_last_month' => $lastMonthNew,
            'growth_rate_percent' => round($growthRate, 2),
            'monthly_trend' => $this->getMonthlyGrowthTrend(12)
        ];
    }

    /**
     * Get monthly growth trend
     */
    private function getMonthlyGrowthTrend(int $months): array
    {
        $trend = [];
        $currentDate = new \DateTimeImmutable('first day of this month');

        for ($i = 0; $i < $months; $i++) {
            $startDate = $currentDate->modify("-{$i} months");
            $endDate = $startDate->modify('last day of this month');

            $newTenants = $this->countNewTenants($startDate, $endDate);

            $trend[] = [
                'month' => $startDate->format('Y-m'),
                'new_tenants' => $newTenants
            ];
        }

        return array_reverse($trend);
    }

    /**
     * Helper: Count tenants by status
     */
    private function countByStatus(array $statuses): int
    {
        return $this->subscriptionRepository->count(['status' => $statuses]);
    }

    /**
     * Helper: Get total unique tenants
     */
    private function getTotalTenants(): int
    {
        $qb = $this->em->createQueryBuilder();
        return (int) $qb->select('COUNT(DISTINCT s.tenantId)')
            ->from(Subscription::class, 's')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Helper: Count new tenants in period
     */
    private function countNewTenants(\DateTimeInterface $startDate, \DateTimeInterface $endDate): int
    {
        $qb = $this->em->createQueryBuilder();
        return (int) $qb->select('COUNT(s.id)')
            ->from(Subscription::class, 's')
            ->where('s.createdAt BETWEEN :start AND :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Helper: Count churned tenants in period
     */
    private function countChurnedTenants(\DateTimeInterface $startDate, \DateTimeInterface $endDate): int
    {
        $qb = $this->em->createQueryBuilder();
        return (int) $qb->select('COUNT(s.id)')
            ->from(Subscription::class, 's')
            ->where('s.canceledAt BETWEEN :start AND :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Calculate activation rate (trial to paid conversion)
     */
    private function calculateActivationRate(): float
    {
        $totalTrials = $this->em->createQueryBuilder()
            ->select('COUNT(s.id)')
            ->from(Subscription::class, 's')
            ->where('s.trialEndsAt IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();

        if ($totalTrials == 0) {
            return 0;
        }

        $convertedToActive = $this->em->createQueryBuilder()
            ->select('COUNT(s.id)')
            ->from(Subscription::class, 's')
            ->where('s.trialEndsAt IS NOT NULL')
            ->andWhere('s.status = :active')
            ->setParameter('active', Subscription::STATUS_ACTIVE)
            ->getQuery()
            ->getSingleScalarResult();

        return round(($convertedToActive / $totalTrials) * 100, 2);
    }

    /**
     * Calculate MRR contribution for a subscription
     */
    private function calculateMRRContribution(Subscription $subscription): float
    {
        if (!$subscription->isActive()) {
            return 0;
        }

        $plan = $subscription->getPlan();
        $price = $plan->getPrice();
        $interval = $plan->getBillingInterval();

        if ($interval === 'monthly') {
            return $price;
        } elseif ($interval === 'yearly') {
            return $price / 12;
        }

        return 0;
    }

    /**
     * Enable/disable tenant access (for support)
     */
    public function toggleTenantAccess(string $tenantId, bool $enabled, string $reason): array
    {
        $this->logger->warning('Toggling tenant access', [
            'tenant_id' => $tenantId,
            'enabled' => $enabled,
            'reason' => $reason
        ]);

        // TODO: Implement actual access control
        // This would set a flag in tenant configuration

        return [
            'success' => true,
            'tenant_id' => $tenantId,
            'access_enabled' => $enabled,
            'reason' => $reason,
            'changed_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s')
        ];
    }

    /**
     * Impersonate tenant (for support)
     */
    public function impersonateTenant(string $tenantId, string $adminUserId): array
    {
        $this->logger->info('Admin impersonating tenant', [
            'tenant_id' => $tenantId,
            'admin_user_id' => $adminUserId
        ]);

        // TODO: Generate impersonation token

        return [
            'success' => true,
            'tenant_id' => $tenantId,
            'impersonation_token' => 'imp_' . bin2hex(random_bytes(16)),
            'expires_at' => (new \DateTimeImmutable('+1 hour'))->format('Y-m-d H:i:s')
        ];
    }
}
