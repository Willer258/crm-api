<?php

namespace App\Repository;

use App\Entity\PasswordResetToken;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PasswordResetToken|null find($id, $lockMode = null, $lockVersion = null)
 * @method PasswordResetToken|null findOneBy(array $criteria, array $orderBy = null)
 * @method PasswordResetToken[]    findAll()
 * @method PasswordResetToken[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PasswordResetTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PasswordResetToken::class);
    }

    /**
     * Find a valid (non-expired, non-used) token by token string
     */
    public function findValidToken(string $token): ?PasswordResetToken
    {
        return $this->createQueryBuilder('prt')
            ->andWhere('prt.token = :token')
            ->andWhere('prt.isUsed = :used')
            ->andWhere('prt.expiresAt > :now')
            ->setParameter('token', $token)
            ->setParameter('used', false)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Mark all tokens for a user as used
     */
    public function markAllAsUsedForUser(User $user): void
    {
        $this->createQueryBuilder('prt')
            ->update()
            ->set('prt.isUsed', ':used')
            ->andWhere('prt.user = :user')
            ->setParameter('used', true)
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    /**
     * Delete expired tokens (cleanup)
     */
    public function deleteExpired(): int
    {
        return $this->createQueryBuilder('prt')
            ->delete()
            ->andWhere('prt.expiresAt < :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->execute();
    }

    /**
     * Count recent reset attempts for a user (to prevent abuse)
     */
    public function countRecentAttemptsForUser(User $user, int $minutes = 60): int
    {
        $since = new \DateTimeImmutable("-{$minutes} minutes");

        return $this->createQueryBuilder('prt')
            ->select('COUNT(prt.id)')
            ->andWhere('prt.user = :user')
            ->andWhere('prt.createdAt > :since')
            ->setParameter('user', $user)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
