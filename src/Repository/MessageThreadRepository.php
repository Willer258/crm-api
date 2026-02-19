<?php

namespace App\Repository;

use App\Entity\Contact;
use App\Entity\MessageThread;
use App\Entity\Workspace;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MessageThread>
 */
class MessageThreadRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MessageThread::class);
    }

    /**
     * Liste des fils de discussion avec filtres
     */
    public function listThreads(array $filters = [], int $page = 1, int $limit = 25): array
    {
        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.contact', 'c')
            ->addSelect('c');

        if (!empty($filters['channel'])) {
            $qb->andWhere('t.channel = :channel')
               ->setParameter('channel', $filters['channel']);
        }

        if (!empty($filters['status'])) {
            $qb->andWhere('t.status = :status')
               ->setParameter('status', $filters['status']);
        }

        if (!empty($filters['contact_id'])) {
            $qb->andWhere('t.contact = :contactId')
               ->setParameter('contactId', $filters['contact_id']);
        }

        if (!empty($filters['workspace_id'])) {
            $qb->andWhere('t.workspace = :workspaceId')
               ->setParameter('workspaceId', $filters['workspace_id']);
        }

        if (!empty($filters['unread_only'])) {
            $qb->andWhere('t.unreadCount > 0');
        }

        if (!empty($filters['search'])) {
            $qb->andWhere('t.contactAddress LIKE :search OR t.subject LIKE :search')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        $qb->orderBy('t.lastMessageAt', 'DESC')
           ->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve un thread existant pour un contact et un canal
     */
    public function findThreadByContactAndChannel(
        Contact $contact,
        string $channel,
        ?Workspace $workspace = null
    ): ?MessageThread {
        $qb = $this->createQueryBuilder('t')
            ->andWhere('t.contact = :contact')
            ->andWhere('t.channel = :channel')
            ->andWhere('t.status = :status')
            ->setParameter('contact', $contact)
            ->setParameter('channel', $channel)
            ->setParameter('status', 'open');

        if ($workspace) {
            $qb->andWhere('t.workspace = :workspace')
               ->setParameter('workspace', $workspace);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Trouve un thread par adresse de contact
     */
    public function findThreadByAddress(
        string $contactAddress,
        string $channel,
        ?Workspace $workspace = null
    ): ?MessageThread {
        $qb = $this->createQueryBuilder('t')
            ->andWhere('t.contactAddress = :address')
            ->andWhere('t.channel = :channel')
            ->andWhere('t.status = :status')
            ->setParameter('address', $contactAddress)
            ->setParameter('channel', $channel)
            ->setParameter('status', 'open');

        if ($workspace) {
            $qb->andWhere('t.workspace = :workspace')
               ->setParameter('workspace', $workspace);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Compte total des non lus
     */
    public function countUnread(?Workspace $workspace = null): int
    {
        $qb = $this->createQueryBuilder('t')
            ->select('SUM(t.unreadCount)');

        if ($workspace) {
            $qb->andWhere('t.workspace = :workspace')
               ->setParameter('workspace', $workspace);
        }

        return (int) ($qb->getQuery()->getSingleScalarResult() ?? 0);
    }

    /**
     * Compte des threads
     */
    public function countThreads(array $filters = []): int
    {
        $qb = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)');

        if (!empty($filters['channel'])) {
            $qb->andWhere('t.channel = :channel')
               ->setParameter('channel', $filters['channel']);
        }

        if (!empty($filters['workspace_id'])) {
            $qb->andWhere('t.workspace = :workspaceId')
               ->setParameter('workspaceId', $filters['workspace_id']);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
