<?php

namespace App\Repository;

use App\Entity\Invoice;
use App\Entity\Subscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Invoice|null find($id, $lockMode = null, $lockVersion = null)
 * @method Invoice|null findOneBy(array $criteria, array $orderBy = null)
 * @method Invoice[]    findAll()
 * @method Invoice[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InvoiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Invoice::class);
    }

    /**
     * Find invoices for a subscription
     *
     * @return Invoice[]
     */
    public function findBySubscription(Subscription $subscription): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.subscription = :subscription')
            ->setParameter('subscription', $subscription)
            ->orderBy('i.invoiceDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find unpaid invoices
     *
     * @return Invoice[]
     */
    public function findUnpaid(): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.status IN (:statuses)')
            ->setParameter('statuses', [Invoice::STATUS_OPEN, Invoice::STATUS_DRAFT])
            ->orderBy('i.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find overdue invoices
     *
     * @return Invoice[]
     */
    public function findOverdue(): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.status = :status')
            ->andWhere('i.dueDate < :now')
            ->setParameter('status', Invoice::STATUS_OPEN)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('i.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find invoices by status
     *
     * @return Invoice[]
     */
    public function findByStatus(string $status): array
    {
        return $this->findBy(['status' => $status], ['invoiceDate' => 'DESC']);
    }

    /**
     * Get next invoice number
     */
    public function getNextInvoiceNumber(): string
    {
        $lastInvoice = $this->createQueryBuilder('i')
            ->orderBy('i.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$lastInvoice) {
            return 'INV-' . date('Y') . '-0001';
        }

        // Extract number from last invoice
        $lastNumber = $lastInvoice->getInvoiceNumber();
        preg_match('/\d+$/', $lastNumber, $matches);

        if (empty($matches)) {
            return 'INV-' . date('Y') . '-0001';
        }

        $nextNumber = (int)$matches[0] + 1;

        return 'INV-' . date('Y') . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate total revenue
     */
    public function calculateTotalRevenue(\DateTimeInterface $startDate = null, \DateTimeInterface $endDate = null): float
    {
        $qb = $this->createQueryBuilder('i')
            ->select('SUM(i.total)')
            ->andWhere('i.status = :status')
            ->setParameter('status', Invoice::STATUS_PAID);

        if ($startDate) {
            $qb->andWhere('i.paidAt >= :start')
               ->setParameter('start', $startDate);
        }

        if ($endDate) {
            $qb->andWhere('i.paidAt <= :end')
               ->setParameter('end', $endDate);
        }

        return (float)$qb->getQuery()->getSingleScalarResult() ?: 0.0;
    }
}
