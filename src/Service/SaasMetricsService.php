<?php

namespace App\Service;

use App\Entity\Subscription;
use App\Entity\Payment;
use App\Repository\SubscriptionRepository;
use App\Repository\PaymentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Advanced SaaS metrics calculation service
 *
 * Calculates key business metrics:
 * - MRR (Monthly Recurring Revenue)
 * - ARR (Annual Recurring Revenue)
 * - Churn Rate
 * - LTV (Lifetime Value)
 * - Customer Acquisition Cost (CAC)
 * - Net Revenue Retention (NRR)
 */
class SaasMetricsService
{
    public function __construct(
        private EntityManagerInterface $em,
        private SubscriptionRepository $subscriptionRepository,
        private PaymentRepository $paymentRepository,
        private LoggerInterface $logger
    ) {}

    /**
     * Calculate Monthly Recurring Revenue (MRR)
     */
    public function calculateMRR(\DateTimeInterface $date = null): array
    {
        $date = $date ?? new \DateTimeImmutable();

        $this->logger->info('Calculating MRR', ['date' => $date->format('Y-m-d')]);

        $qb = $this->em->createQueryBuilder();
        $qb->select('s', 'p')
           ->from(Subscription::class, 's')
           ->join('s.plan', 'p')
           ->where('s.status IN (:statuses)')
           ->andWhere('s.currentPeriodEnd >= :date')
           ->setParameter('statuses', [Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIALING])
           ->setParameter('date', $date);

        $subscriptions = $qb->getQuery()->getResult();

        $mrr = 0;
        $breakdown = [
            'monthly_subscriptions' => 0,
            'yearly_subscriptions' => 0,
            'monthly_mrr' => 0,
            'yearly_mrr' => 0
        ];

        foreach ($subscriptions as $subscription) {
            $plan = $subscription->getPlan();
            $price = $plan->getPrice();
            $interval = $plan->getBillingInterval();

            if ($interval === 'monthly') {
                $breakdown['monthly_subscriptions']++;
                $breakdown['monthly_mrr'] += $price;
                $mrr += $price;
            } elseif ($interval === 'yearly') {
                $breakdown['yearly_subscriptions']++;
                $monthlyEquivalent = $price / 12;
                $breakdown['yearly_mrr'] += $monthlyEquivalent;
                $mrr += $monthlyEquivalent;
            }
        }

        return [
            'mrr' => round($mrr, 2),
            'total_subscriptions' => count($subscriptions),
            'breakdown' => $breakdown,
            'calculated_at' => $date->format('Y-m-d H:i:s')
        ];
    }

    /**
     * Calculate Annual Recurring Revenue (ARR)
     */
    public function calculateARR(\DateTimeInterface $date = null): array
    {
        $mrrData = $this->calculateMRR($date);
        $arr = $mrrData['mrr'] * 12;

        return [
            'arr' => round($arr, 2),
            'mrr' => $mrrData['mrr'],
            'total_subscriptions' => $mrrData['total_subscriptions'],
            'calculated_at' => $mrrData['calculated_at']
        ];
    }

    /**
     * Calculate Churn Rate for a given period
     *
     * @param \DateTimeInterface $startDate Start of period
     * @param \DateTimeInterface $endDate End of period
     * @return array Churn metrics
     */
    public function calculateChurnRate(
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate
    ): array {
        $this->logger->info('Calculating churn rate', [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d')
        ]);

        // Count customers at start of period
        $qbStart = $this->em->createQueryBuilder();
        $qbStart->select('COUNT(s.id)')
                ->from(Subscription::class, 's')
                ->where('s.startDate <= :startDate')
                ->andWhere('s.status IN (:active_statuses)')
                ->setParameter('startDate', $startDate)
                ->setParameter('active_statuses', [
                    Subscription::STATUS_ACTIVE,
                    Subscription::STATUS_TRIALING
                ]);

        $customersAtStart = (int) $qbStart->getQuery()->getSingleScalarResult();

        // Count churned customers (canceled during period)
        $qbChurned = $this->em->createQueryBuilder();
        $qbChurned->select('COUNT(s.id)')
                  ->from(Subscription::class, 's')
                  ->where('s.canceledAt BETWEEN :startDate AND :endDate')
                  ->orWhere('s.status = :canceled')
                  ->andWhere('s.updatedAt BETWEEN :startDate AND :endDate')
                  ->setParameter('startDate', $startDate)
                  ->setParameter('endDate', $endDate)
                  ->setParameter('canceled', Subscription::STATUS_CANCELED);

        $churnedCustomers = (int) $qbChurned->getQuery()->getSingleScalarResult();

        // Calculate churn rate
        $churnRate = $customersAtStart > 0
            ? ($churnedCustomers / $customersAtStart) * 100
            : 0;

        // Calculate retention rate
        $retentionRate = 100 - $churnRate;

        return [
            'customers_at_start' => $customersAtStart,
            'churned_customers' => $churnedCustomers,
            'churn_rate' => round($churnRate, 2),
            'retention_rate' => round($retentionRate, 2),
            'period_start' => $startDate->format('Y-m-d'),
            'period_end' => $endDate->format('Y-m-d')
        ];
    }

    /**
     * Calculate Customer Lifetime Value (LTV)
     */
    public function calculateLTV(): array
    {
        $this->logger->info('Calculating LTV');

        // Calculate average revenue per user (ARPU)
        $mrrData = $this->calculateMRR();
        $arpu = $mrrData['total_subscriptions'] > 0
            ? $mrrData['mrr'] / $mrrData['total_subscriptions']
            : 0;

        // Calculate average customer lifespan (in months)
        // Using historical data from canceled subscriptions
        $qb = $this->em->createQueryBuilder();
        $qb->select('s')
           ->from(Subscription::class, 's')
           ->where('s.status = :canceled')
           ->andWhere('s.startDate IS NOT NULL')
           ->andWhere('s.endDate IS NOT NULL')
           ->setParameter('canceled', Subscription::STATUS_CANCELED);

        $canceledSubscriptions = $qb->getQuery()->getResult();

        if (empty($canceledSubscriptions)) {
            // No historical data, use industry standard (24 months)
            $avgLifespanMonths = 24;
            $isEstimated = true;
        } else {
            $totalLifespanMonths = 0;
            foreach ($canceledSubscriptions as $subscription) {
                $diff = $subscription->getStartDate()->diff($subscription->getEndDate());
                $months = ($diff->y * 12) + $diff->m;
                $totalLifespanMonths += $months;
            }

            $avgLifespanMonths = $totalLifespanMonths / count($canceledSubscriptions);
            $isEstimated = false;
        }

        // LTV = ARPU × Average Customer Lifespan (in months)
        $ltv = $arpu * $avgLifespanMonths;

        return [
            'ltv' => round($ltv, 2),
            'arpu' => round($arpu, 2),
            'avg_lifespan_months' => round($avgLifespanMonths, 2),
            'is_estimated' => $isEstimated,
            'total_canceled_subscriptions_analyzed' => count($canceledSubscriptions)
        ];
    }

    /**
     * Calculate Customer Acquisition Cost (CAC)
     * Requires marketing/sales cost data
     *
     * @param float $totalMarketingCost Total marketing and sales costs for period
     * @param \DateTimeInterface $startDate Period start
     * @param \DateTimeInterface $endDate Period end
     */
    public function calculateCAC(
        float $totalMarketingCost,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate
    ): array {
        $this->logger->info('Calculating CAC', [
            'marketing_cost' => $totalMarketingCost,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d')
        ]);

        // Count new customers acquired during period
        $qb = $this->em->createQueryBuilder();
        $qb->select('COUNT(s.id)')
           ->from(Subscription::class, 's')
           ->where('s.createdAt BETWEEN :startDate AND :endDate')
           ->setParameter('startDate', $startDate)
           ->setParameter('endDate', $endDate);

        $newCustomers = (int) $qb->getQuery()->getSingleScalarResult();

        // CAC = Total Marketing & Sales Cost / Number of New Customers
        $cac = $newCustomers > 0 ? $totalMarketingCost / $newCustomers : 0;

        // Calculate LTV:CAC ratio
        $ltvData = $this->calculateLTV();
        $ltvCacRatio = $cac > 0 ? $ltvData['ltv'] / $cac : 0;

        return [
            'cac' => round($cac, 2),
            'total_marketing_cost' => $totalMarketingCost,
            'new_customers_acquired' => $newCustomers,
            'ltv' => $ltvData['ltv'],
            'ltv_cac_ratio' => round($ltvCacRatio, 2),
            'period_start' => $startDate->format('Y-m-d'),
            'period_end' => $endDate->format('Y-m-d'),
            'health_status' => $this->getCACHealthStatus($ltvCacRatio)
        ];
    }

    /**
     * Determine CAC health status based on LTV:CAC ratio
     */
    private function getCACHealthStatus(float $ltvCacRatio): string
    {
        return match(true) {
            $ltvCacRatio >= 3 => 'excellent', // Industry standard is 3:1
            $ltvCacRatio >= 2 => 'good',
            $ltvCacRatio >= 1 => 'acceptable',
            default => 'poor'
        };
    }

    /**
     * Calculate Net Revenue Retention (NRR)
     */
    public function calculateNRR(
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate
    ): array {
        $this->logger->info('Calculating NRR', [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d')
        ]);

        // Get subscriptions active at start of period
        $qbStart = $this->em->createQueryBuilder();
        $qbStart->select('s', 'p')
                ->from(Subscription::class, 's')
                ->join('s.plan', 'p')
                ->where('s.startDate <= :startDate')
                ->andWhere('s.status IN (:active_statuses)')
                ->setParameter('startDate', $startDate)
                ->setParameter('active_statuses', [
                    Subscription::STATUS_ACTIVE,
                    Subscription::STATUS_TRIALING
                ]);

        $startSubscriptions = $qbStart->getQuery()->getResult();
        $startMRR = $this->calculateMRRForSubscriptions($startSubscriptions);

        // Get same cohort at end of period (expansions, contractions, churns)
        $tenantIds = array_map(fn($s) => $s->getTenantId(), $startSubscriptions);

        if (empty($tenantIds)) {
            return [
                'nrr' => 0,
                'start_mrr' => 0,
                'end_mrr' => 0,
                'period_start' => $startDate->format('Y-m-d'),
                'period_end' => $endDate->format('Y-m-d')
            ];
        }

        $qbEnd = $this->em->createQueryBuilder();
        $qbEnd->select('s', 'p')
              ->from(Subscription::class, 's')
              ->join('s.plan', 'p')
              ->where('s.tenantId IN (:tenantIds)')
              ->andWhere('s.currentPeriodEnd >= :endDate')
              ->setParameter('tenantIds', $tenantIds)
              ->setParameter('endDate', $endDate);

        $endSubscriptions = $qbEnd->getQuery()->getResult();
        $endMRR = $this->calculateMRRForSubscriptions($endSubscriptions);

        // NRR = (End MRR / Start MRR) * 100
        $nrr = $startMRR > 0 ? ($endMRR / $startMRR) * 100 : 0;

        return [
            'nrr' => round($nrr, 2),
            'start_mrr' => round($startMRR, 2),
            'end_mrr' => round($endMRR, 2),
            'expansion_mrr' => round(max(0, $endMRR - $startMRR), 2),
            'contraction_mrr' => round(max(0, $startMRR - $endMRR), 2),
            'cohort_size' => count($startSubscriptions),
            'retained_customers' => count($endSubscriptions),
            'period_start' => $startDate->format('Y-m-d'),
            'period_end' => $endDate->format('Y-m-d'),
            'health_status' => $nrr >= 100 ? 'healthy' : 'needs_attention'
        ];
    }

    /**
     * Helper: Calculate MRR for a set of subscriptions
     */
    private function calculateMRRForSubscriptions(array $subscriptions): float
    {
        $mrr = 0;

        foreach ($subscriptions as $subscription) {
            $plan = $subscription->getPlan();
            $price = $plan->getPrice();
            $interval = $plan->getBillingInterval();

            if ($interval === 'monthly') {
                $mrr += $price;
            } elseif ($interval === 'yearly') {
                $mrr += $price / 12;
            }
        }

        return $mrr;
    }

    /**
     * Get comprehensive dashboard metrics
     */
    public function getDashboardMetrics(): array
    {
        $now = new \DateTimeImmutable();
        $lastMonth = $now->modify('-1 month');
        $lastYear = $now->modify('-1 year');

        return [
            'mrr' => $this->calculateMRR(),
            'arr' => $this->calculateARR(),
            'churn_last_month' => $this->calculateChurnRate($lastMonth, $now),
            'churn_last_year' => $this->calculateChurnRate($lastYear, $now),
            'ltv' => $this->calculateLTV(),
            'nrr_last_year' => $this->calculateNRR($lastYear, $now),
            'subscription_breakdown' => $this->getSubscriptionBreakdown(),
            'revenue_trends' => $this->getRevenueTrends(6), // Last 6 months
            'generated_at' => $now->format('Y-m-d H:i:s')
        ];
    }

    /**
     * Get subscription breakdown by plan
     */
    public function getSubscriptionBreakdown(): array
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('p.name as plan_name, p.code as plan_code, COUNT(s.id) as count, p.price')
           ->from(Subscription::class, 's')
           ->join('s.plan', 'p')
           ->where('s.status IN (:statuses)')
           ->setParameter('statuses', [Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIALING])
           ->groupBy('p.id, p.name, p.code, p.price');

        return $qb->getQuery()->getResult();
    }

    /**
     * Get revenue trends for last N months
     */
    public function getRevenueTrends(int $months = 12): array
    {
        $trends = [];
        $currentDate = new \DateTimeImmutable('first day of this month');

        for ($i = 0; $i < $months; $i++) {
            $date = $currentDate->modify("-{$i} months");
            $mrrData = $this->calculateMRR($date);

            $trends[] = [
                'month' => $date->format('Y-m'),
                'mrr' => $mrrData['mrr'],
                'subscriptions' => $mrrData['total_subscriptions']
            ];
        }

        return array_reverse($trends);
    }

    /**
     * Export metrics to array for reporting
     */
    public function exportMetrics(\DateTimeInterface $date = null): array
    {
        $date = $date ?? new \DateTimeImmutable();
        $lastMonth = $date->modify('-1 month');

        return [
            'report_date' => $date->format('Y-m-d'),
            'metrics' => [
                'mrr' => $this->calculateMRR($date),
                'arr' => $this->calculateARR($date),
                'churn' => $this->calculateChurnRate($lastMonth, $date),
                'ltv' => $this->calculateLTV(),
                'nrr' => $this->calculateNRR($lastMonth, $date)
            ],
            'trends' => $this->getRevenueTrends(12),
            'breakdown' => $this->getSubscriptionBreakdown()
        ];
    }
}
