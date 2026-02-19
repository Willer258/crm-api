<?php

namespace App\Repository;

use App\Entity\EmailOtp;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EmailOtp>
 */
class EmailOtpRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmailOtp::class);
    }

    /**
     * Trouve le dernier OTP valide pour un email et un purpose donné
     */
    public function findValidOtp(string $email, string $purpose): ?EmailOtp
    {
        return $this->createQueryBuilder('o')
            ->where('o.email = :email')
            ->andWhere('o.purpose = :purpose')
            ->andWhere('o.isUsed = false')
            ->andWhere('o.expiresAt > :now')
            ->andWhere('o.attempts < 5')
            ->setParameter('email', $email)
            ->setParameter('purpose', $purpose)
            ->setParameter('now', new \DateTime())
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Invalide tous les OTP d'un utilisateur pour un purpose donné
     */
    public function invalidateUserOtps(User $user, string $purpose): void
    {
        $this->createQueryBuilder('o')
            ->update()
            ->set('o.isUsed', true)
            ->where('o.user = :user')
            ->andWhere('o.purpose = :purpose')
            ->andWhere('o.isUsed = false')
            ->setParameter('user', $user)
            ->setParameter('purpose', $purpose)
            ->getQuery()
            ->execute();
    }

    /**
     * Invalide tous les OTP d'un email pour un purpose donné
     */
    public function invalidateEmailOtps(string $email, string $purpose): void
    {
        $this->createQueryBuilder('o')
            ->update()
            ->set('o.isUsed', true)
            ->where('o.email = :email')
            ->andWhere('o.purpose = :purpose')
            ->andWhere('o.isUsed = false')
            ->setParameter('email', $email)
            ->setParameter('purpose', $purpose)
            ->getQuery()
            ->execute();
    }

    /**
     * Compte les tentatives récentes depuis une IP
     */
    public function countRecentAttempts(string $ipAddress, int $minutes = 15): int
    {
        $since = (new \DateTime())->modify("-{$minutes} minutes");

        return (int) $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.ipAddress = :ip')
            ->andWhere('o.createdAt > :since')
            ->setParameter('ip', $ipAddress)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Nettoie les OTP expirés (pour une commande cron)
     */
    public function cleanExpiredOtps(int $daysOld = 7): int
    {
        $expiredDate = (new \DateTime())->modify("-{$daysOld} days");

        return $this->createQueryBuilder('o')
            ->delete()
            ->where('o.createdAt < :expiredDate')
            ->setParameter('expiredDate', $expiredDate)
            ->getQuery()
            ->execute();
    }
}
