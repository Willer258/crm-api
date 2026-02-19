<?php

namespace App\Repository;

use App\Entity\PropertyModel;
use App\Service\WorkspaceResolver;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends WorkspaceAwareRepository<PropertyModel>
 */
class PropertyModelRepository extends WorkspaceAwareRepository
{
    public function __construct(ManagerRegistry $registry, WorkspaceResolver $workspaceResolver)
    {
        parent::__construct($registry, PropertyModel::class, $workspaceResolver);
    }

    //    /**
    //     * @return PropertyModel[] Returns an array of PropertyModel objects
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

    //    public function findOneBySomeField($value): ?PropertyModel
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
