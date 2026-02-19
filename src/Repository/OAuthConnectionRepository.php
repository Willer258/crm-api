<?php

namespace App\Repository;

use App\Entity\OAuthConnection;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository pour la gestion des connexions OAuth
 *
 * @method OAuthConnection|null find($id, $lockMode = null, $lockVersion = null)
 * @method OAuthConnection|null findOneBy(array $criteria, array $orderBy = null)
 * @method OAuthConnection[]    findAll()
 * @method OAuthConnection[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OAuthConnectionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OAuthConnection::class);
    }

    /**
     * Find an OAuth connection by provider and provider user ID
     */
    public function findByProviderAndUserId(string $provider, string $providerUserId): ?OAuthConnection
    {
        return $this->findOneBy([
            'provider' => $provider,
            'providerUserId' => $providerUserId,
        ]);
    }

    /**
     * Find all OAuth connections for a user
     *
     * @return OAuthConnection[]
     */
    public function findByUser(User $user): array
    {
        return $this->findBy(['user' => $user], ['connectedAt' => 'DESC']);
    }

    /**
     * Find a specific provider connection for a user
     */
    public function findByUserAndProvider(User $user, string $provider): ?OAuthConnection
    {
        return $this->findOneBy([
            'user' => $user,
            'provider' => $provider,
        ]);
    }

    /**
     * Check if a user has a connection to a specific provider
     */
    public function hasProviderConnection(User $user, string $provider): bool
    {
        return $this->findByUserAndProvider($user, $provider) !== null;
    }

    /**
     * Get all users connected via a specific provider
     *
     * @return OAuthConnection[]
     */
    public function findByProvider(string $provider): array
    {
        return $this->findBy(['provider' => $provider]);
    }

    /**
     * Count connections per provider
     *
     * @return array<string, int>
     */
    public function countByProvider(): array
    {
        $results = $this->createQueryBuilder('oc')
            ->select('oc.provider, COUNT(oc.id) as count')
            ->groupBy('oc.provider')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($results as $row) {
            $counts[$row['provider']] = (int) $row['count'];
        }

        return $counts;
    }

    /**
     * Delete all connections for a user
     */
    public function deleteAllForUser(User $user): int
    {
        return $this->createQueryBuilder('oc')
            ->delete()
            ->where('oc.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    /**
     * Delete a specific provider connection for a user
     */
    public function deleteByUserAndProvider(User $user, string $provider): int
    {
        return $this->createQueryBuilder('oc')
            ->delete()
            ->where('oc.user = :user')
            ->andWhere('oc.provider = :provider')
            ->setParameter('user', $user)
            ->setParameter('provider', $provider)
            ->getQuery()
            ->execute();
    }

    /**
     * Find connections with expired tokens that have refresh tokens
     * (useful for batch token refresh)
     *
     * @return OAuthConnection[]
     */
    public function findExpiredWithRefreshToken(): array
    {
        return $this->createQueryBuilder('oc')
            ->where('oc.tokenExpiresAt < :now')
            ->andWhere('oc.refreshToken IS NOT NULL')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getResult();
    }
}
