<?php

namespace App\Repository;

use App\Entity\RefreshToken;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method RefreshToken|null find($id, $lockMode = null, $lockVersion = null)
 * @method RefreshToken|null findOneBy(array $criteria, array $orderBy = null)
 * @method RefreshToken[]    findAll()
 * @method RefreshToken[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RefreshTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RefreshToken::class);
    }

    /**
     * Find all valid (non-expired, non-revoked) refresh tokens for a user
     *
     * @return RefreshToken[]
     */
    public function findValidTokensForUser(User $user): array
    {
        return $this->createQueryBuilder('rt')
            ->andWhere('rt.user = :user')
            ->andWhere('rt.isRevoked = :revoked')
            ->andWhere('rt.expiresAt > :now')
            ->setParameter('user', $user)
            ->setParameter('revoked', false)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('rt.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Revoke all refresh tokens for a user
     */
    public function revokeAllForUser(User $user): void
    {
        $this->createQueryBuilder('rt')
            ->update()
            ->set('rt.isRevoked', ':revoked')
            ->andWhere('rt.user = :user')
            ->setParameter('revoked', true)
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    /**
     * Delete expired refresh tokens (cleanup)
     */
    public function deleteExpired(): int
    {
        return $this->createQueryBuilder('rt')
            ->delete()
            ->andWhere('rt.expiresAt < :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->execute();
    }
}
