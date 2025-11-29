<?php

namespace App\Repository;

use App\Entity\Payment;
use App\Entity\Invoice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Payment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Payment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Payment[]    findAll()
 * @method Payment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Payment::class);
    }

    /**
     * Find payments for an invoice
     *
     * @return Payment[]
     */
    public function findByInvoice(Invoice $invoice): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.invoice = :invoice')
            ->setParameter('invoice', $invoice)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find failed payments
     *
     * @return Payment[]
     */
    public function findFailed(): array
    {
        return $this->findBy(['status' => Payment::STATUS_FAILED], ['failedAt' => 'DESC']);
    }

    /**
     * Find pending payments
     *
     * @return Payment[]
     */
    public function findPending(): array
    {
        return $this->findBy(['status' => Payment::STATUS_PENDING], ['createdAt' => 'ASC']);
    }

    /**
     * Calculate success rate
     */
    public function calculateSuccessRate(\DateTimeInterface $startDate = null, \DateTimeInterface $endDate = null): float
    {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)');

        if ($startDate) {
            $qb->andWhere('p.createdAt >= :start')
               ->setParameter('start', $startDate);
        }

        if ($endDate) {
            $qb->andWhere('p.createdAt <= :end')
               ->setParameter('end', $endDate);
        }

        $total = (int)$qb->getQuery()->getSingleScalarResult();

        if ($total === 0) {
            return 0.0;
        }

        $qb = clone $qb;
        $succeeded = (int)$qb->andWhere('p.status = :status')
            ->setParameter('status', Payment::STATUS_SUCCEEDED)
            ->getQuery()
            ->getSingleScalarResult();

        return round(($succeeded / $total) * 100, 2);
    }
}
