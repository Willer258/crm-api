<?php

namespace App\Repository;

use App\Entity\LoginHistory;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method LoginHistory|null find($id, $lockMode = null, $lockVersion = null)
 * @method LoginHistory|null findOneBy(array $criteria, array $orderBy = null)
 * @method LoginHistory[]    findAll()
 * @method LoginHistory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LoginHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LoginHistory::class);
    }

    /**
     * Get login history for a user
     *
     * @return LoginHistory[]
     */
    public function findByUser(User $user, int $limit = 10): array
    {
        return $this->createQueryBuilder('lh')
            ->andWhere('lh.user = :user')
            ->setParameter('user', $user)
            ->orderBy('lh.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Count failed login attempts for a user in a time period
     */
    public function countFailedAttemptsForUser(?User $user, int $minutes = 15): int
    {
        $since = new \DateTimeImmutable("-{$minutes} minutes");

        $qb = $this->createQueryBuilder('lh')
            ->select('COUNT(lh.id)')
            ->andWhere('lh.success = :success')
            ->andWhere('lh.createdAt > :since')
            ->setParameter('success', false)
            ->setParameter('since', $since);

        if ($user) {
            $qb->andWhere('lh.user = :user')
               ->setParameter('user', $user);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Count failed login attempts from an IP address in a time period
     */
    public function countFailedAttemptsByIp(string $ipAddress, int $minutes = 15): int
    {
        $since = new \DateTimeImmutable("-{$minutes} minutes");

        return $this->createQueryBuilder('lh')
            ->select('COUNT(lh.id)')
            ->andWhere('lh.ipAddress = :ip')
            ->andWhere('lh.success = :success')
            ->andWhere('lh.createdAt > :since')
            ->setParameter('ip', $ipAddress)
            ->setParameter('success', false)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get recent successful logins for a user
     *
     * @return LoginHistory[]
     */
    public function findRecentSuccessfulLogins(User $user, int $limit = 5): array
    {
        return $this->createQueryBuilder('lh')
            ->andWhere('lh.user = :user')
            ->andWhere('lh.success = :success')
            ->setParameter('user', $user)
            ->setParameter('success', true)
            ->orderBy('lh.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Delete old login history records (cleanup)
     */
    public function deleteOlderThan(\DateTimeInterface $date): int
    {
        return $this->createQueryBuilder('lh')
            ->delete()
            ->andWhere('lh.createdAt < :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->execute();
    }

    /**
     * Get suspicious login attempts (multiple failed attempts from different IPs)
     *
     * @return array
     */
    public function findSuspiciousActivity(int $minutes = 60, int $threshold = 5): array
    {
        $since = new \DateTimeImmutable("-{$minutes} minutes");

        return $this->createQueryBuilder('lh')
            ->select('lh.user, COUNT(lh.id) as attempts')
            ->andWhere('lh.success = :success')
            ->andWhere('lh.createdAt > :since')
            ->setParameter('success', false)
            ->setParameter('since', $since)
            ->groupBy('lh.user')
            ->having('COUNT(lh.id) >= :threshold')
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->getResult();
    }
}
