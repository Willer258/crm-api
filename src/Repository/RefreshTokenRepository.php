<?php

namespace App\Repository;

use App\Entity\RefreshToken;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository pour la gestion des refresh tokens
 *
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
     * Find a refresh token by its plain text token
     *
     * This method hashes the plain token and looks up by hash
     * for secure token storage/retrieval.
     */
    public function findByPlainToken(string $plainToken): ?RefreshToken
    {
        $tokenHash = RefreshToken::hashToken($plainToken);

        return $this->findOneBy(['tokenHash' => $tokenHash]);
    }

    /**
     * Find a valid (non-expired, non-revoked) refresh token by plain token
     */
    public function findValidByPlainToken(string $plainToken): ?RefreshToken
    {
        $token = $this->findByPlainToken($plainToken);

        if ($token && $token->isValid()) {
            return $token;
        }

        return null;
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
     * Find all refresh tokens (including revoked/expired) for a user
     * Useful for session management UI
     *
     * @return RefreshToken[]
     */
    public function findAllTokensForUser(User $user, int $limit = 20): array
    {
        return $this->createQueryBuilder('rt')
            ->andWhere('rt.user = :user')
            ->setParameter('user', $user)
            ->orderBy('rt.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Count active sessions for a user
     */
    public function countActiveSessionsForUser(User $user): int
    {
        return (int) $this->createQueryBuilder('rt')
            ->select('COUNT(rt.id)')
            ->andWhere('rt.user = :user')
            ->andWhere('rt.isRevoked = :revoked')
            ->andWhere('rt.expiresAt > :now')
            ->setParameter('user', $user)
            ->setParameter('revoked', false)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Revoke all refresh tokens for a user
     */
    public function revokeAllForUser(User $user): int
    {
        return $this->createQueryBuilder('rt')
            ->update()
            ->set('rt.isRevoked', ':revoked')
            ->andWhere('rt.user = :user')
            ->andWhere('rt.isRevoked = :notRevoked')
            ->setParameter('revoked', true)
            ->setParameter('notRevoked', false)
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    /**
     * Revoke a specific token by ID for a user
     * Returns true if token was found and revoked
     */
    public function revokeTokenForUser(int $tokenId, User $user): bool
    {
        $result = $this->createQueryBuilder('rt')
            ->update()
            ->set('rt.isRevoked', ':revoked')
            ->andWhere('rt.id = :id')
            ->andWhere('rt.user = :user')
            ->setParameter('revoked', true)
            ->setParameter('id', $tokenId)
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();

        return $result > 0;
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

    /**
     * Delete revoked tokens older than specified days
     */
    public function deleteRevokedOlderThan(int $days = 7): int
    {
        $cutoffDate = (new \DateTimeImmutable())->modify("-{$days} days");

        return $this->createQueryBuilder('rt')
            ->delete()
            ->andWhere('rt.isRevoked = :revoked')
            ->andWhere('rt.createdAt < :cutoff')
            ->setParameter('revoked', true)
            ->setParameter('cutoff', $cutoffDate)
            ->getQuery()
            ->execute();
    }

    /**
     * Clean up old tokens (both expired and old revoked)
     * Useful for scheduled cleanup tasks
     */
    public function cleanup(): array
    {
        $expiredCount = $this->deleteExpired();
        $revokedCount = $this->deleteRevokedOlderThan(7);

        return [
            'expired_deleted' => $expiredCount,
            'revoked_deleted' => $revokedCount,
        ];
    }
}
