<?php

namespace App\Repository;

use App\Entity\UsageMetric;
use App\Entity\Subscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method UsageMetric|null find($id, $lockMode = null, $lockVersion = null)
 * @method UsageMetric|null findOneBy(array $criteria, array $orderBy = null)
 * @method UsageMetric[]    findAll()
 * @method UsageMetric[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UsageMetricRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UsageMetric::class);
    }

    /**
     * Get latest metrics for a subscription
     *
     * @return UsageMetric[]
     */
    public function findLatestForSubscription(Subscription $subscription): array
    {
        return $this->createQueryBuilder('um')
            ->andWhere('um.subscription = :subscription')
            ->setParameter('subscription', $subscription)
            ->orderBy('um.metricDate', 'DESC')
            ->setMaxResults(100)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get metrics for a specific date range
     *
     * @return UsageMetric[]
     */
    public function findByDateRange(
        Subscription $subscription,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate
    ): array {
        return $this->createQueryBuilder('um')
            ->andWhere('um.subscription = :subscription')
            ->andWhere('um.metricDate >= :start')
            ->andWhere('um.metricDate <= :end')
            ->setParameter('subscription', $subscription)
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->orderBy('um.metricDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get aggregated usage for a subscription
     */
    public function getAggregatedUsage(Subscription $subscription, int $days = 30): array
    {
        $endDate = new \DateTime();
        $startDate = (new \DateTime())->modify("-{$days} days");

        $metrics = $this->findByDateRange($subscription, $startDate, $endDate);

        $aggregated = [];

        foreach ($metrics as $metric) {
            $type = $metric->getMetricType();

            if (!isset($aggregated[$type])) {
                $aggregated[$type] = [
                    'total' => 0,
                    'average' => 0,
                    'peak' => 0,
                    'count' => 0,
                ];
            }

            $value = $metric->getValue();
            $aggregated[$type]['total'] += $value;
            $aggregated[$type]['peak'] = max($aggregated[$type]['peak'], $value);
            $aggregated[$type]['count']++;
        }

        // Calculate averages
        foreach ($aggregated as $type => &$data) {
            if ($data['count'] > 0) {
                $data['average'] = round($data['total'] / $data['count'], 2);
            }
        }

        return $aggregated;
    }

    /**
     * Find metrics exceeding quota
     *
     * @return UsageMetric[]
     */
    public function findExceedingQuota(): array
    {
        $allMetrics = $this->findAll();

        return array_filter($allMetrics, function(UsageMetric $metric) {
            return $metric->isQuotaExceeded();
        });
    }

    /**
     * Delete old metrics (cleanup)
     */
    public function deleteOlderThan(\DateTimeInterface $date): int
    {
        return $this->createQueryBuilder('um')
            ->delete()
            ->andWhere('um.metricDate < :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->execute();
    }
}
