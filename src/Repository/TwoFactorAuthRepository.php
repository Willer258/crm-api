<?php

namespace App\Repository;

use App\Entity\TwoFactorAuth;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository pour la gestion des configurations 2FA
 *
 * @method TwoFactorAuth|null find($id, $lockMode = null, $lockVersion = null)
 * @method TwoFactorAuth|null findOneBy(array $criteria, array $orderBy = null)
 * @method TwoFactorAuth[]    findAll()
 * @method TwoFactorAuth[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TwoFactorAuthRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TwoFactorAuth::class);
    }

    /**
     * Trouve ou crée une configuration 2FA pour un utilisateur
     */
    public function findOrCreateForUser(User $user): TwoFactorAuth
    {
        $twoFactorAuth = $this->findOneBy(['user' => $user]);

        if (!$twoFactorAuth) {
            $twoFactorAuth = new TwoFactorAuth($user);
            $this->getEntityManager()->persist($twoFactorAuth);
        }

        return $twoFactorAuth;
    }

    /**
     * Vérifie si un utilisateur a activé le 2FA
     */
    public function isEnabledForUser(User $user): bool
    {
        $twoFactorAuth = $this->findOneBy(['user' => $user]);

        return $twoFactorAuth?->isEnabled() ?? false;
    }

    /**
     * Retourne les statistiques 2FA
     */
    public function getStatistics(): array
    {
        $qb = $this->createQueryBuilder('tfa');

        $total = (int) $qb
            ->select('COUNT(tfa.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $enabled = (int) $this->createQueryBuilder('tfa')
            ->select('COUNT(tfa.id)')
            ->where('tfa.isEnabled = :enabled')
            ->setParameter('enabled', true)
            ->getQuery()
            ->getSingleScalarResult();

        $usedRecently = (int) $this->createQueryBuilder('tfa')
            ->select('COUNT(tfa.id)')
            ->where('tfa.isEnabled = :enabled')
            ->andWhere('tfa.lastUsedAt >= :since')
            ->setParameter('enabled', true)
            ->setParameter('since', (new \DateTimeImmutable())->modify('-7 days'))
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total' => $total,
            'enabled' => $enabled,
            'disabled' => $total - $enabled,
            'usedLastWeek' => $usedRecently,
            'adoptionRate' => $total > 0 ? round(($enabled / $total) * 100, 1) : 0,
        ];
    }

    /**
     * Trouve les utilisateurs avec peu de codes de récupération restants
     *
     * @return TwoFactorAuth[]
     */
    public function findWithLowRecoveryCodes(int $threshold = 3): array
    {
        // Note: Cette requête charge tous les enregistrements activés
        // puis filtre en PHP car JSON_LENGTH n'est pas disponible partout
        $all = $this->createQueryBuilder('tfa')
            ->where('tfa.isEnabled = :enabled')
            ->setParameter('enabled', true)
            ->getQuery()
            ->getResult();

        return array_filter($all, fn(TwoFactorAuth $tfa) => $tfa->getRecoveryCodesCount() <= $threshold);
    }
}
