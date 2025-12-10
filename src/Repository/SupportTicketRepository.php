<?php

namespace App\Repository;

use App\Entity\SupportTicket;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SupportTicket>
 */
class SupportTicketRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SupportTicket::class);
    }

    /**
     * Find tickets with pagination
     */
    public function findWithPagination(array $criteria = [], int $limit = 25, int $offset = 0): array
    {
        $qb = $this->createQueryBuilder('t');

        if (!empty($criteria['status'])) {
            $qb->andWhere('t.status = :status')
               ->setParameter('status', $criteria['status']);
        }

        if (!empty($criteria['priority'])) {
            $qb->andWhere('t.priority = :priority')
               ->setParameter('priority', $criteria['priority']);
        }

        if (!empty($criteria['tenantId'])) {
            $qb->andWhere('t.tenantId = :tenantId')
               ->setParameter('tenantId', $criteria['tenantId']);
        }

        $qb->orderBy('t.createdAt', 'DESC')
           ->setMaxResults($limit)
           ->setFirstResult($offset);

        return $qb->getQuery()->getResult();
    }
}
