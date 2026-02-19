<?php

namespace App\Repository;

use App\Entity\Pipeline;
use App\Service\WorkspaceResolver;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends WorkspaceAwareRepository<Pipeline>
 */
class PipelineRepository extends WorkspaceAwareRepository
{
    public function __construct(ManagerRegistry $registry, WorkspaceResolver $workspaceResolver)
    {
        parent::__construct($registry, Pipeline::class, $workspaceResolver);
    }

    /**
     * @return Pipeline[] Returns all pipelines ordered by creation date (newest first)
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.removeAt IS NULL')
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Pipeline[] Returns an array of active Pipeline objects
     */
    public function listPipelines($data): array
    {
        $qb = $this->createQueryBuilder('p');
        $qb->where('p.removeAt IS NULL');

        // 📄 Pagination
        $page = max((int)($data['pagination']['page'] ?? 1), 1);
        $limit = min((int)($data['pagination']['limit'] ?? 25), 100);
        $offset = ($page - 1) * $limit;

        // 📋 Tri par date de création (du plus récent au plus ancien)
        $qb->orderBy('p.createdAt', 'DESC');

        $qb->setFirstResult($offset)->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Pipeline[] Returns an array of deleted Pipeline objects
     */
    public function listDeletedPipelines($data): array
    {
        $qb = $this->createQueryBuilder('p');
        $qb->where('p.removeAt IS NOT NULL');

        // 📄 Pagination
        $page = max((int)($data['pagination']['page'] ?? 1), 1);
        $limit = min((int)($data['pagination']['limit'] ?? 25), 100);
        $offset = ($page - 1) * $limit;

        // 📋 Tri par date de suppression (du plus récent au plus ancien)
        $qb->orderBy('p.removeAt', 'DESC');

        $qb->setFirstResult($offset)->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Pipeline[] Returns an array of all Pipeline objects (active + deleted)
     */
    public function listAllPipelines($data): array
    {
        $qb = $this->createQueryBuilder('p');

        // 📄 Pagination
        $page = max((int)($data['pagination']['page'] ?? 1), 1);
        $limit = min((int)($data['pagination']['limit'] ?? 25), 100);
        $offset = ($page - 1) * $limit;

        // 📋 Tri par date de création (du plus récent au plus ancien)
        $qb->orderBy('p.createdAt', 'DESC');

        $qb->setFirstResult($offset)->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    public function getCount(): int
    {
        $qb = $this->createQueryBuilder('p');
        return $qb->select($qb->expr()->countDistinct('p.id'))
            ->andWhere('p.removeAt IS NULL')
            ->getQuery()->getSingleScalarResult();
    }

    public function getDeletedCount(): int
    {
        $qb = $this->createQueryBuilder('p');
        return $qb->select($qb->expr()->countDistinct('p.id'))
            ->andWhere('p.removeAt IS NOT NULL')
            ->getQuery()->getSingleScalarResult();
    }

    public function getAllCount(): int
    {
        $qb = $this->createQueryBuilder('p');
        return $qb->select($qb->expr()->countDistinct('p.id'))
            ->getQuery()->getSingleScalarResult();
    }

//    /**
//     * @return Pipeline[] Returns an array of Pipeline objects
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

//    public function findOneBySomeField($value): ?Pipeline
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
