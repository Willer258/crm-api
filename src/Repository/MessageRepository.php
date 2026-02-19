<?php

namespace App\Repository;

use App\Entity\Message;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Message>
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    /**
     * Liste messages avec filtres
     */
    public function listMessages(array $filters = [], int $page = 1, int $limit = 25): array
    {
        $qb = $this->createQueryBuilder('m')
            ->leftJoin('m.contact', 'c')
            ->leftJoin('m.thread', 't')
            ->addSelect('c', 't');

        if (!empty($filters['channel'])) {
            $qb->andWhere('m.channel = :channel')
               ->setParameter('channel', $filters['channel']);
        }

        if (!empty($filters['direction'])) {
            $qb->andWhere('m.direction = :direction')
               ->setParameter('direction', $filters['direction']);
        }

        if (!empty($filters['status'])) {
            $qb->andWhere('m.status = :status')
               ->setParameter('status', $filters['status']);
        }

        if (!empty($filters['contact_id'])) {
            $qb->andWhere('m.contact = :contactId')
               ->setParameter('contactId', $filters['contact_id']);
        }

        if (!empty($filters['thread_id'])) {
            $qb->andWhere('m.thread = :threadId')
               ->setParameter('threadId', $filters['thread_id']);
        }

        if (!empty($filters['workspace_id'])) {
            $qb->andWhere('m.workspace = :workspaceId')
               ->setParameter('workspaceId', $filters['workspace_id']);
        }

        $qb->orderBy('m.createdAt', 'DESC')
           ->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * Compte total avec filtres
     */
    public function countMessages(array $filters = []): int
    {
        $qb = $this->createQueryBuilder('m')
            ->select('COUNT(m.id)');

        if (!empty($filters['channel'])) {
            $qb->andWhere('m.channel = :channel')
               ->setParameter('channel', $filters['channel']);
        }

        if (!empty($filters['contact_id'])) {
            $qb->andWhere('m.contact = :contactId')
               ->setParameter('contactId', $filters['contact_id']);
        }

        if (!empty($filters['workspace_id'])) {
            $qb->andWhere('m.workspace = :workspaceId')
               ->setParameter('workspaceId', $filters['workspace_id']);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
