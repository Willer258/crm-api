<?php

namespace App\Repository;

use App\Entity\AccountLockout;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository pour la gestion des verrouillages de compte
 *
 * @method AccountLockout|null find($id, $lockMode = null, $lockVersion = null)
 * @method AccountLockout|null findOneBy(array $criteria, array $orderBy = null)
 * @method AccountLockout[]    findAll()
 * @method AccountLockout[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AccountLockoutRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccountLockout::class);
    }

    /**
     * Trouve ou crée un enregistrement de verrouillage pour un utilisateur
     */
    public function findOrCreateForUser(User $user): AccountLockout
    {
        $lockout = $this->findOneBy(['user' => $user]);

        if (!$lockout) {
            $lockout = new AccountLockout($user);
            $this->getEntityManager()->persist($lockout);
        }

        return $lockout;
    }

    /**
     * Vérifie si un utilisateur est actuellement verrouillé
     */
    public function isUserLocked(User $user): bool
    {
        $lockout = $this->findOneBy(['user' => $user]);

        if (!$lockout) {
            return false;
        }

        return $lockout->isLocked();
    }

    /**
     * Retourne les informations de verrouillage si l'utilisateur est verrouillé
     */
    public function getLockoutInfo(User $user): ?array
    {
        $lockout = $this->findOneBy(['user' => $user]);

        if (!$lockout || !$lockout->isLocked()) {
            return null;
        }

        return [
            'locked' => true,
            'lockedUntil' => $lockout->getLockedUntil()->format('c'),
            'remainingSeconds' => $lockout->getRemainingLockTime(),
            'remainingFormatted' => $lockout->getRemainingLockTimeFormatted(),
            'failedAttempts' => $lockout->getFailedAttempts(),
        ];
    }

    /**
     * Enregistre un échec de connexion
     *
     * @return array Informations sur le statut du verrouillage
     */
    public function recordFailedAttempt(User $user, ?string $ipAddress = null): array
    {
        $lockout = $this->findOrCreateForUser($user);
        $lockout->incrementFailedAttempts($ipAddress);

        $this->getEntityManager()->flush();

        return [
            'locked' => $lockout->isLocked(),
            'failedAttempts' => $lockout->getFailedAttempts(),
            'attemptsRemaining' => $lockout->getAttemptsRemainingBeforeLockout(),
            'lockedUntil' => $lockout->isLocked() ? $lockout->getLockedUntil()->format('c') : null,
            'remainingSeconds' => $lockout->getRemainingLockTime(),
        ];
    }

    /**
     * Réinitialise le verrouillage après une connexion réussie
     */
    public function resetOnSuccessfulLogin(User $user): void
    {
        $lockout = $this->findOneBy(['user' => $user]);

        if ($lockout) {
            $lockout->resetLockout();
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Compte les échecs de connexion depuis une IP spécifique
     * sur une période donnée (pour la détection d'attaques)
     */
    public function countFailedAttemptsFromIp(string $ipAddress, int $minutes = 60): int
    {
        $since = (new \DateTimeImmutable())->modify("-{$minutes} minutes");

        return (int) $this->createQueryBuilder('al')
            ->select('SUM(al.failedAttempts)')
            ->where('al.lastIpAddress = :ip')
            ->andWhere('al.lastFailedAttempt >= :since')
            ->setParameter('ip', $ipAddress)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
    }

    /**
     * Trouve les comptes actuellement verrouillés
     *
     * @return AccountLockout[]
     */
    public function findCurrentlyLocked(): array
    {
        return $this->createQueryBuilder('al')
            ->where('al.lockedUntil > :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('al.lockedUntil', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les comptes avec un nombre élevé de tentatives échouées
     * (potentiel signe d'attaque)
     *
     * @return AccountLockout[]
     */
    public function findSuspiciousActivity(int $threshold = 10, int $hoursWindow = 24): array
    {
        $since = (new \DateTimeImmutable())->modify("-{$hoursWindow} hours");

        return $this->createQueryBuilder('al')
            ->where('al.failedAttempts >= :threshold')
            ->andWhere('al.lastFailedAttempt >= :since')
            ->setParameter('threshold', $threshold)
            ->setParameter('since', $since)
            ->orderBy('al.failedAttempts', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Nettoie les anciens enregistrements de verrouillage
     * (comptes sans échec depuis plus de X jours)
     */
    public function cleanupOldRecords(int $days = 30): int
    {
        $cutoff = (new \DateTimeImmutable())->modify("-{$days} days");

        return $this->createQueryBuilder('al')
            ->delete()
            ->where('al.lastFailedAttempt < :cutoff')
            ->andWhere('al.failedAttempts = 0')
            ->setParameter('cutoff', $cutoff)
            ->getQuery()
            ->execute();
    }
}
