<?php

namespace App\Repository;

use App\Entity\Deal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Deal>
 */
class DealRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Deal::class);
    }

    /**
     * @return Deal[] Returns an array of active Deal objects
     */
    public function listDeals($data)
    {
        $qb = $this->createQueryBuilder('d');

        $qb->select('DISTINCT d')
            ->leftJoin('d.contact', 'c')
            ->leftJoin('d.company', 'co')
            ->leftJoin('d.step', 's')
            ->leftJoin('d.tags', 't')
            ->where('d.removeAt IS NULL');

        // 📄 Pagination
        $page = max((int)($data['pagination']['page'] ?? 1), 1);
        $limit = min((int)($data['pagination']['limit'] ?? 25), 100);
        $offset = ($page - 1) * $limit;

        // 📋 Tri par date de création (du plus récent au plus ancien)
        $qb->orderBy('d.createdAt', 'DESC');

        $qb->setFirstResult($offset)->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Deal[] Returns an array of deleted Deal objects
     */
    public function listDeletedDeals($data)
    {
        $qb = $this->createQueryBuilder('d');

        $qb->select('DISTINCT d')
            ->leftJoin('d.contact', 'c')
            ->leftJoin('d.company', 'co')
            ->leftJoin('d.step', 's')
            ->leftJoin('d.tags', 't')
            ->where('d.removeAt IS NOT NULL');

        // 📄 Pagination
        $page = max((int)($data['pagination']['page'] ?? 1), 1);
        $limit = min((int)($data['pagination']['limit'] ?? 25), 100);
        $offset = ($page - 1) * $limit;

        // 📋 Tri par date de suppression (du plus récent au plus ancien)
        $qb->orderBy('d.removeAt', 'DESC');

        $qb->setFirstResult($offset)->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Deal[] Returns an array of all Deal objects (active + deleted)
     */
    public function listAllDeals($data)
    {
        $qb = $this->createQueryBuilder('d');

        $qb->select('DISTINCT d')
            ->leftJoin('d.contact', 'c')
            ->leftJoin('d.company', 'co')
            ->leftJoin('d.step', 's')
            ->leftJoin('d.tags', 't');

        // 📄 Pagination
        $page = max((int)($data['pagination']['page'] ?? 1), 1);
        $limit = min((int)($data['pagination']['limit'] ?? 25), 100);
        $offset = ($page - 1) * $limit;

        // 📋 Tri par date de création (du plus récent au plus ancien)
        $qb->orderBy('d.createdAt', 'DESC');

        $qb->setFirstResult($offset)->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    public function getCount(): int
    {
        $qb = $this->createQueryBuilder('d');
        return $qb->select($qb->expr()->countDistinct('d.id'))
            ->andWhere('d.removeAt IS NULL')
            ->getQuery()->getSingleScalarResult();
    }

    public function getDeletedCount(): int
    {
        $qb = $this->createQueryBuilder('d');
        return $qb->select($qb->expr()->countDistinct('d.id'))
            ->andWhere('d.removeAt IS NOT NULL')
            ->getQuery()->getSingleScalarResult();
    }

    public function getAllCount(): int
    {
        $qb = $this->createQueryBuilder('d');
        return $qb->select($qb->expr()->countDistinct('d.id'))
            ->getQuery()->getSingleScalarResult();
    }
}
