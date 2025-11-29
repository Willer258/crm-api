<?php

namespace App\Repository;

use App\Entity\Quota;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Quota|null find($id, $lockMode = null, $lockVersion = null)
 * @method Quota|null findOneBy(array $criteria, array $orderBy = null)
 * @method Quota[]    findAll()
 * @method Quota[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class QuotaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Quota::class);
    }

    /**
     * Find quotas that are approaching their limit
     *
     * @return Quota[]
     */
    public function findApproachingLimit(int $threshold = 80): array
    {
        // This would require a complex query or post-processing in PHP
        // For now, we fetch all and filter
        $allQuotas = $this->findAll();

        return array_filter($allQuotas, function(Quota $quota) use ($threshold) {
            return !$quota->isUnlimited() && $quota->getUsagePercentage() >= $threshold;
        });
    }

    /**
     * Find exceeded quotas
     *
     * @return Quota[]
     */
    public function findExceeded(): array
    {
        $allQuotas = $this->findAll();

        return array_filter($allQuotas, function(Quota $quota) {
            return $quota->isExceeded();
        });
    }

    /**
     * Get quotas for tenants with exceeded limits
     */
    public function findTenantsWithExceededQuotas(): array
    {
        $exceededQuotas = $this->findExceeded();
        $tenants = [];

        foreach ($exceededQuotas as $quota) {
            $tenantId = $quota->getTenantId();
            if (!isset($tenants[$tenantId])) {
                $tenants[$tenantId] = [];
            }
            $tenants[$tenantId][] = $quota;
        }

        return $tenants;
    }
}
