<?php

namespace App\Repository;

use App\Entity\PipelineStep;
use App\Service\WorkspaceResolver;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends WorkspaceAwareRepository<PipelineStep>
 */
class PipelineStepRepository extends WorkspaceAwareRepository
{
    public function __construct(ManagerRegistry $registry, WorkspaceResolver $workspaceResolver)
    {
        parent::__construct($registry, PipelineStep::class, $workspaceResolver);
    }

    //    /**
    //     * @return PipelineStep[] Returns an array of PipelineStep objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?PipelineStep
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
