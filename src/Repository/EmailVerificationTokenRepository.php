<?php

namespace App\Repository;

use App\Entity\EmailVerificationToken;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method EmailVerificationToken|null find($id, $lockMode = null, $lockVersion = null)
 * @method EmailVerificationToken|null findOneBy(array $criteria, array $orderBy = null)
 * @method EmailVerificationToken[]    findAll()
 * @method EmailVerificationToken[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EmailVerificationTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmailVerificationToken::class);
    }

    /**
     * Find a valid (non-expired, non-used) token by token string
     */
    public function findValidToken(string $token): ?EmailVerificationToken
    {
        return $this->createQueryBuilder('evt')
            ->andWhere('evt.token = :token')
            ->andWhere('evt.isUsed = :used')
            ->andWhere('evt.expiresAt > :now')
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
        $this->createQueryBuilder('evt')
            ->update()
            ->set('evt.isUsed', ':used')
            ->andWhere('evt.user = :user')
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
        return $this->createQueryBuilder('evt')
            ->delete()
            ->andWhere('evt.expiresAt < :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->execute();
    }
}
