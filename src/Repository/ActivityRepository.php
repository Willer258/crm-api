<?php

namespace App\Repository;

use App\Entity\Activity;
use App\Service\WorkspaceResolver;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends WorkspaceAwareRepository<Activity>
 */
class ActivityRepository extends WorkspaceAwareRepository
{
    public function __construct(ManagerRegistry $registry, WorkspaceResolver $workspaceResolver)
    {
        parent::__construct($registry, Activity::class, $workspaceResolver);
    }

    /**
     * Retourne toutes les activités avec filtres optionnels
     * @param array $filters
     * @return Activity[]
     */
    public function findByFilters(array $filters = []): array
    {
        $qb = $this->createQueryBuilder('a');

        // Par défaut, exclure les activités supprimées
        $qb->andWhere('a.removeAt IS NULL');

        // Filtre par manager
        if (!empty($filters['manager'])) {
            $qb->andWhere('a.managers LIKE :manager')
               ->setParameter('manager', '%"' . $filters['manager'] . '"%');
        }

        // Filtre par deal
        if (!empty($filters['deal'])) {
            $qb->andWhere('a.deal = :deal')
               ->setParameter('deal', $filters['deal']);
        }

        // Filtre par contact
        if (!empty($filters['contact'])) {
            $qb->andWhere('a.contact = :contact')
               ->setParameter('contact', $filters['contact']);
        }

        // Filtre par company
        if (!empty($filters['company'])) {
            $qb->andWhere('a.company = :company')
               ->setParameter('company', $filters['company']);
        }

        // Filtre par statut (performed)
        if (isset($filters['performed'])) {
            $qb->andWhere('a.performed = :performed')
               ->setParameter('performed', (bool)$filters['performed']);
        }

        // Tri par date de création (du plus récent au plus ancien)
        $qb->orderBy('a.createdAt', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Activity[] Returns an array of active Activity objects
     */
    public function listActivities($data): array
    {
        $qb = $this->createQueryBuilder('a');
        $qb->where('a.removeAt IS NULL');

        // 📄 Pagination
        $page = max((int)($data['pagination']['page'] ?? 1), 1);
        $limit = min((int)($data['pagination']['limit'] ?? 25), 100);
        $offset = ($page - 1) * $limit;

        // 📋 Tri par date de création (du plus récent au plus ancien)
        $qb->orderBy('a.createdAt', 'DESC');

        $qb->setFirstResult($offset)->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Activity[] Returns an array of deleted Activity objects
     */
    public function listDeletedActivities($data): array
    {
        $qb = $this->createQueryBuilder('a');
        $qb->where('a.removeAt IS NOT NULL');

        // 📄 Pagination
        $page = max((int)($data['pagination']['page'] ?? 1), 1);
        $limit = min((int)($data['pagination']['limit'] ?? 25), 100);
        $offset = ($page - 1) * $limit;

        // 📋 Tri par date de suppression (du plus récent au plus ancien)
        $qb->orderBy('a.removeAt', 'DESC');

        $qb->setFirstResult($offset)->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Activity[] Returns an array of all Activity objects (active + deleted)
     */
    public function listAllActivities($data): array
    {
        $qb = $this->createQueryBuilder('a');

        // 📄 Pagination
        $page = max((int)($data['pagination']['page'] ?? 1), 1);
        $limit = min((int)($data['pagination']['limit'] ?? 25), 100);
        $offset = ($page - 1) * $limit;

        // 📋 Tri par date de création (du plus récent au plus ancien)
        $qb->orderBy('a.createdAt', 'DESC');

        $qb->setFirstResult($offset)->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    public function getCount(): int
    {
        $qb = $this->createQueryBuilder('a');
        return $qb->select($qb->expr()->countDistinct('a.id'))
            ->andWhere('a.removeAt IS NULL')
            ->getQuery()->getSingleScalarResult();
    }

    public function getDeletedCount(): int
    {
        $qb = $this->createQueryBuilder('a');
        return $qb->select($qb->expr()->countDistinct('a.id'))
            ->andWhere('a.removeAt IS NOT NULL')
            ->getQuery()->getSingleScalarResult();
    }

    public function getAllCount(): int
    {
        $qb = $this->createQueryBuilder('a');
        return $qb->select($qb->expr()->countDistinct('a.id'))
            ->getQuery()->getSingleScalarResult();
    }

    /**
     * Retourne toutes les activités entre deux dates, groupées par date d'exécution (startDate)
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array<string, Activity[]>
     */
    public function findByDateRangeGrouped($startDate, $endDate, $dealId = null , $data = null): array
    {
        $qb = $this->createQueryBuilder('a');
        if ($startDate) {
            $qb->andWhere('a.startDate >= :startDate')->setParameter('startDate', new \DateTime($startDate));
        }
        if ($endDate) {
            $qb->andWhere('a.endDate <= :endDate')->setParameter('endDate', new \DateTime($endDate));
        }
        if (isset($dealId)) {
            $qb->andWhere('a.deal = :dealId')->setParameter('dealId', $dealId);
        }
        
        $qb->andWhere('a.removeAt IS NULL');

        if (!empty($data['managers'])) {
            $orX = $qb->expr()->orX();
            foreach ($data['managers'] as $index => $manager) {
                $orX->add($qb->expr()->like('a.managers', ':manager_' . $index));
                $qb->setParameter('manager_' . $index, '%"' . $manager . '"%');
            }
            $qb->andWhere($orX);
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
