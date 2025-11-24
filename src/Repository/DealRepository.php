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

        // 🔍 Filtre par statut
        if (!empty($data['filters']['status'])) {
            $status = $data['filters']['status'];
            if (in_array($status, [Deal::STATUS_WIN, Deal::STATUS_LOST], true)) {
                $qb->andWhere('d.status = :status')
                   ->setParameter('status', $status);
            } elseif ($status === 'open') {
                // Affaires ouvertes (ni gagnées ni perdues)
                $qb->andWhere('d.status IS NULL');
            }
        }

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

    public function getCount($data = []): int
    {
        $qb = $this->createQueryBuilder('d');
        $qb->select($qb->expr()->countDistinct('d.id'))
            ->andWhere('d.removeAt IS NULL');

        // 🔍 Filtre par statut (même logique que listDeals)
        if (!empty($data['filters']['status'])) {
            $status = $data['filters']['status'];
            if (in_array($status, [Deal::STATUS_WIN, Deal::STATUS_LOST], true)) {
                $qb->andWhere('d.status = :status')
                   ->setParameter('status', $status);
            } elseif ($status === 'open') {
                $qb->andWhere('d.status IS NULL');
            }
        }

        return $qb->getQuery()->getSingleScalarResult();
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

    /**
     * @return Deal[] Returns an array of won Deal objects
     */
    public function findWonDeals(?array $pagination = null): array
    {
        $qb = $this->createQueryBuilder('d');

        $qb->select('DISTINCT d')
            ->leftJoin('d.contact', 'c')
            ->leftJoin('d.company', 'co')
            ->leftJoin('d.step', 's')
            ->leftJoin('d.tags', 't')
            ->where('d.removeAt IS NULL')
            ->andWhere('d.status = :status')
            ->setParameter('status', Deal::STATUS_WIN)
            ->orderBy('d.createdAt', 'DESC');

        if ($pagination) {
            $page = max((int)($pagination['page'] ?? 1), 1);
            $limit = min((int)($pagination['limit'] ?? 25), 100);
            $offset = ($page - 1) * $limit;
            $qb->setFirstResult($offset)->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Deal[] Returns an array of lost Deal objects
     */
    public function findLostDeals(?array $pagination = null): array
    {
        $qb = $this->createQueryBuilder('d');

        $qb->select('DISTINCT d')
            ->leftJoin('d.contact', 'c')
            ->leftJoin('d.company', 'co')
            ->leftJoin('d.step', 's')
            ->leftJoin('d.tags', 't')
            ->where('d.removeAt IS NULL')
            ->andWhere('d.status = :status')
            ->setParameter('status', Deal::STATUS_LOST)
            ->orderBy('d.createdAt', 'DESC');

        if ($pagination) {
            $page = max((int)($pagination['page'] ?? 1), 1);
            $limit = min((int)($pagination['limit'] ?? 25), 100);
            $offset = ($page - 1) * $limit;
            $qb->setFirstResult($offset)->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Get count of won deals
     */
    public function getWonCount(): int
    {
        $qb = $this->createQueryBuilder('d');
        return $qb->select($qb->expr()->countDistinct('d.id'))
            ->andWhere('d.removeAt IS NULL')
            ->andWhere('d.status = :status')
            ->setParameter('status', Deal::STATUS_WIN)
            ->getQuery()->getSingleScalarResult();
    }

    /**
     * Get count of lost deals
     */
    public function getLostCount(): int
    {
        $qb = $this->createQueryBuilder('d');
        return $qb->select($qb->expr()->countDistinct('d.id'))
            ->andWhere('d.removeAt IS NULL')
            ->andWhere('d.status = :status')
            ->setParameter('status', Deal::STATUS_LOST)
            ->getQuery()->getSingleScalarResult();
    }
}
