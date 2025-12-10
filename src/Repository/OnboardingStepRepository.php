<?php

namespace App\Repository;

use App\Entity\OnboardingStep;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OnboardingStep>
 */
class OnboardingStepRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OnboardingStep::class);
    }

    /**
     * Find all steps for a tenant ordered by orderIndex
     */
    public function findByTenant(string $tenantId): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.tenantId = :tenantId')
            ->setParameter('tenantId', $tenantId)
            ->orderBy('o.orderIndex', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get onboarding progress for tenant
     */
    public function getProgress(string $tenantId): array
    {
        $qb = $this->createQueryBuilder('o')
            ->where('o.tenantId = :tenantId')
            ->setParameter('tenantId', $tenantId);

        $all = $qb->getQuery()->getResult();
        $total = count($all);

        if ($total === 0) {
            return [
                'total' => 0,
                'completed' => 0,
                'percentage' => 0,
                'is_complete' => false
            ];
        }

        $completed = array_filter($all, fn($step) => $step->isCompleted());
        $completedCount = count($completed);

        return [
            'total' => $total,
            'completed' => $completedCount,
            'percentage' => round(($completedCount / $total) * 100, 2),
            'is_complete' => $completedCount === $total
        ];
    }

    /**
     * Get next pending step
     */
    public function getNextPendingStep(string $tenantId): ?OnboardingStep
    {
        return $this->createQueryBuilder('o')
            ->where('o.tenantId = :tenantId')
            ->andWhere('o.status = :status')
            ->setParameter('tenantId', $tenantId)
            ->setParameter('status', OnboardingStep::STATUS_PENDING)
            ->orderBy('o.orderIndex', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Check if onboarding is complete
     */
    public function isOnboardingComplete(string $tenantId): bool
    {
        $progress = $this->getProgress($tenantId);
        return $progress['is_complete'];
    }
}
