<?php

namespace App\Repository;

use App\Entity\MagicLink;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository pour la gestion des magic links (authentification sans mot de passe)
 *
 * @method MagicLink|null find($id, $lockMode = null, $lockVersion = null)
 * @method MagicLink|null findOneBy(array $criteria, array $orderBy = null)
 * @method MagicLink[]    findAll()
 * @method MagicLink[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MagicLinkRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MagicLink::class);
    }

    /**
     * Find a magic link by its plain text token
     *
     * This method hashes the plain token and looks up by hash
     * for secure token storage/retrieval.
     */
    public function findByPlainToken(string $plainToken): ?MagicLink
    {
        $tokenHash = MagicLink::hashToken($plainToken);

        return $this->findOneBy(['tokenHash' => $tokenHash]);
    }

    /**
     * Find a valid (non-used, non-expired) magic link by plain token
     */
    public function findValidByPlainToken(string $plainToken): ?MagicLink
    {
        $magicLink = $this->findByPlainToken($plainToken);

        if ($magicLink && $magicLink->isValid()) {
            return $magicLink;
        }

        return null;
    }

    /**
     * Find all pending (not used, not expired) magic links for a user
     *
     * @return MagicLink[]
     */
    public function findPendingForUser(User $user): array
    {
        return $this->createQueryBuilder('ml')
            ->andWhere('ml.user = :user')
            ->andWhere('ml.isUsed = :used')
            ->andWhere('ml.expiresAt > :now')
            ->setParameter('user', $user)
            ->setParameter('used', false)
            ->setParameter('now', new \DateTime())
            ->orderBy('ml.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Invalidate all pending magic links for a user
     * Used when a new magic link is created to prevent multiple active links
     */
    public function invalidateAllForUser(User $user): int
    {
        return $this->createQueryBuilder('ml')
            ->update()
            ->set('ml.isUsed', ':used')
            ->andWhere('ml.user = :user')
            ->andWhere('ml.isUsed = :notUsed')
            ->setParameter('used', true)
            ->setParameter('notUsed', false)
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    /**
     * Count recent magic link requests for rate limiting
     */
    public function countRecentRequestsForUser(User $user, int $minutes = 15): int
    {
        $since = (new \DateTime())->modify("-{$minutes} minutes");

        return (int) $this->createQueryBuilder('ml')
            ->select('COUNT(ml.id)')
            ->andWhere('ml.user = :user')
            ->andWhere('ml.createdAt > :since')
            ->setParameter('user', $user)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Count recent magic link requests from an IP for rate limiting
     */
    public function countRecentRequestsFromIp(string $ipAddress, int $minutes = 15): int
    {
        $since = (new \DateTime())->modify("-{$minutes} minutes");

        return (int) $this->createQueryBuilder('ml')
            ->select('COUNT(ml.id)')
            ->andWhere('ml.ipAddress = :ip')
            ->andWhere('ml.createdAt > :since')
            ->setParameter('ip', $ipAddress)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Delete expired magic links (cleanup)
     */
    public function deleteExpired(): int
    {
        return $this->createQueryBuilder('ml')
            ->delete()
            ->andWhere('ml.expiresAt < :now')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->execute();
    }

    /**
     * Delete used magic links older than specified days
     */
    public function deleteUsedOlderThan(int $days = 7): int
    {
        $cutoffDate = (new \DateTime())->modify("-{$days} days");

        return $this->createQueryBuilder('ml')
            ->delete()
            ->andWhere('ml.isUsed = :used')
            ->andWhere('ml.createdAt < :cutoff')
            ->setParameter('used', true)
            ->setParameter('cutoff', $cutoffDate)
            ->getQuery()
            ->execute();
    }

    /**
     * Clean up old magic links (both expired and old used)
     * Useful for scheduled cleanup tasks
     */
    public function cleanup(): array
    {
        $expiredCount = $this->deleteExpired();
        $usedCount = $this->deleteUsedOlderThan(7);

        return [
            'expired_deleted' => $expiredCount,
            'used_deleted' => $usedCount,
        ];
    }
}
