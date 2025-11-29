<?php

namespace App\Repository;

use App\Entity\Subscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Subscription|null find($id, $lockMode = null, $lockVersion = null)
 * @method Subscription|null findOneBy(array $criteria, array $orderBy = null)
 * @method Subscription[]    findAll()
 * @method Subscription[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Subscription::class);
    }

    /**
     * Find active subscription for tenant
     */
    public function findActiveForTenant(string $tenantId): ?Subscription
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.tenantId = :tenant')
            ->andWhere('s.status IN (:statuses)')
            ->setParameter('tenant', $tenantId)
            ->setParameter('statuses', [Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIALING])
            ->orderBy('s.startDate', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find subscriptions expiring soon
     *
     * @return Subscription[]
     */
    public function findExpiringSoon(int $days = 7): array
    {
        $date = new \DateTimeImmutable("+{$days} days");

        return $this->createQueryBuilder('s')
            ->andWhere('s.status = :status')
            ->andWhere('s.currentPeriodEnd <= :date')
            ->andWhere('s.currentPeriodEnd >= :now')
            ->setParameter('status', Subscription::STATUS_ACTIVE)
            ->setParameter('date', $date)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getResult();
    }

    /**
     * Find subscriptions in trial
     *
     * @return Subscription[]
     */
    public function findInTrial(): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.status = :status')
            ->andWhere('s.trialEndsAt > :now')
            ->setParameter('status', Subscription::STATUS_TRIALING)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getResult();
    }

    /**
     * Find trial subscriptions ending soon
     *
     * @return Subscription[]
     */
    public function findTrialsEndingSoon(int $days = 3): array
    {
        $date = new \DateTimeImmutable("+{$days} days");

        return $this->createQueryBuilder('s')
            ->andWhere('s.status = :status')
            ->andWhere('s.trialEndsAt <= :date')
            ->andWhere('s.trialEndsAt >= :now')
            ->setParameter('status', Subscription::STATUS_TRIALING)
            ->setParameter('date', $date)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getResult();
    }

    /**
     * Find past due subscriptions
     *
     * @return Subscription[]
     */
    public function findPastDue(): array
    {
        return $this->findBy(['status' => Subscription::STATUS_PAST_DUE]);
    }

    /**
     * Get subscription statistics
     */
    public function getStatistics(): array
    {
        $qb = $this->createQueryBuilder('s');

        $total = $qb->select('COUNT(s.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $active = $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->andWhere('s.status = :status')
            ->setParameter('status', Subscription::STATUS_ACTIVE)
            ->getQuery()
            ->getSingleScalarResult();

        $trialing = $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->andWhere('s.status = :status')
            ->setParameter('status', Subscription::STATUS_TRIALING)
            ->getQuery()
            ->getSingleScalarResult();

        $pastDue = $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->andWhere('s.status = :status')
            ->setParameter('status', Subscription::STATUS_PAST_DUE)
            ->getQuery()
            ->getSingleScalarResult();

        $canceled = $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->andWhere('s.status = :status')
            ->setParameter('status', Subscription::STATUS_CANCELED)
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total' => $total,
            'active' => $active,
            'trialing' => $trialing,
            'past_due' => $pastDue,
            'canceled' => $canceled,
        ];
    }

    /**
     * Calculate Monthly Recurring Revenue (MRR)
     */
    public function calculateMRR(): float
    {
        $subscriptions = $this->createQueryBuilder('s')
            ->join('s.plan', 'p')
            ->andWhere('s.status IN (:statuses)')
            ->setParameter('statuses', [Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIALING])
            ->getQuery()
            ->getResult();

        $mrr = 0;

        foreach ($subscriptions as $subscription) {
            $plan = $subscription->getPlan();
            $price = $plan->getPrice();

            // Normalize to monthly
            if ($plan->getBillingInterval() === 'yearly') {
                $price = $price / 12;
            }

            $mrr += $price;
        }

        return round($mrr, 2);
    }
}
