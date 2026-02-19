<?php

namespace App\Repository;

use App\Entity\Note;
use App\Service\WorkspaceResolver;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends WorkspaceAwareRepository<Note>
 */
class NoteRepository extends WorkspaceAwareRepository
{
    public function __construct(ManagerRegistry $registry, WorkspaceResolver $workspaceResolver)
    {
        parent::__construct($registry, Note::class, $workspaceResolver);
    }

    /**
     * @return Note[] Returns an array of active Note objects
     */
    public function listNotes($data): array
    {
        $qb = $this->createQueryBuilder('n');
        $qb->where('n.removeAt IS NULL');

        // 📄 Pagination
        $page = max((int)($data['pagination']['page'] ?? 1), 1);
        $limit = min((int)($data['pagination']['limit'] ?? 25), 100);
        $offset = ($page - 1) * $limit;

        // 📋 Tri par date de création (du plus récent au plus ancien)
        $qb->orderBy('n.createdAt', 'DESC');

        $qb->setFirstResult($offset)->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Note[] Returns an array of deleted Note objects
     */
    public function listDeletedNotes($data): array
    {
        $qb = $this->createQueryBuilder('n');
        $qb->where('n.removeAt IS NOT NULL');

        // 📄 Pagination
        $page = max((int)($data['pagination']['page'] ?? 1), 1);
        $limit = min((int)($data['pagination']['limit'] ?? 25), 100);
        $offset = ($page - 1) * $limit;

        // 📋 Tri par date de suppression (du plus récent au plus ancien)
        $qb->orderBy('n.removeAt', 'DESC');

        $qb->setFirstResult($offset)->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Note[] Returns an array of all Note objects (active + deleted)
     */
    public function listAllNotes($data): array
    {
        $qb = $this->createQueryBuilder('n');

        // 📄 Pagination
        $page = max((int)($data['pagination']['page'] ?? 1), 1);
        $limit = min((int)($data['pagination']['limit'] ?? 25), 100);
        $offset = ($page - 1) * $limit;

        // 📋 Tri par date de création (du plus récent au plus ancien)
        $qb->orderBy('n.createdAt', 'DESC');

        $qb->setFirstResult($offset)->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    public function getCount(): int
    {
        $qb = $this->createQueryBuilder('n');
        return $qb->select($qb->expr()->countDistinct('n.id'))
            ->andWhere('n.removeAt IS NULL')
            ->getQuery()->getSingleScalarResult();
    }

    public function getDeletedCount(): int
    {
        $qb = $this->createQueryBuilder('n');
        return $qb->select($qb->expr()->countDistinct('n.id'))
            ->andWhere('n.removeAt IS NOT NULL')
            ->getQuery()->getSingleScalarResult();
    }

    public function getAllCount(): int
    {
        $qb = $this->createQueryBuilder('n');
        return $qb->select($qb->expr()->countDistinct('n.id'))
            ->getQuery()->getSingleScalarResult();
    }

//    /**
//     * @return Note[] Returns an array of Note objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('n')
//            ->andWhere('n.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('n.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Note
//    {
//        return $this->createQueryBuilder('n')
//            ->andWhere('n.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
