<?php

namespace App\Repository;

use App\Entity\Activity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Activity>
 */
class ActivityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Activity::class);
    }

    /**
     * Retourne toutes les activités entre deux dates, groupées par date d'exécution (startDate)
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array<string, Activity[]>
     */
    public function findByDateRangeGrouped($startDate, $endDate, $dealId = null): array
    {
        $qb = $this->createQueryBuilder('a');
        if ($startDate) {
            $qb->andWhere('a.startDate >= :startDate')->setParameter('startDate', new \DateTime($startDate));
        }
        if ($endDate) {
            $qb->andWhere('a.endDate <= :endDate')->setParameter('endDate', new \DateTime($endDate));
        }
        if ($dealId) {
            $qb->andWhere('a.deal = :dealId')->setParameter('dealId', $dealId);
        }
        $qb->orderBy('a.startDate', 'ASC');
        $results = $qb->getQuery()->getResult();
        // Grouper par date d'exécution (startDate)
        $calendar = [];
        foreach ($results as $activity) {
            $dateKey = $activity->getStartDate()?->format('Y-m-d');
            if ($dateKey) {
                $calendar[$dateKey][] = $activity;
            }
        }
        return $calendar;
    }

    //    /**
    //     * @return Activity[] Returns an array of Activity objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Activity
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
