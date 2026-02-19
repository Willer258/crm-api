<?php

namespace App\Entity;

use App\Repository\AccountLockoutRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Gestion du verrouillage de compte après échecs de connexion
 *
 * Implémente un verrouillage progressif:
 * - 5 échecs -> 5 minutes
 * - 10 échecs -> 15 minutes
 * - 15 échecs -> 1 heure
 * - 20+ échecs -> 24 heures
 */
#[ORM\Entity(repositoryClass: AccountLockoutRepository::class)]
#[ORM\Table(name: 'account_lockouts')]
#[ORM\Index(columns: ['user_id', 'locked_until'], name: 'idx_user_lockout')]
#[ORM\Index(columns: ['last_ip_address', 'last_failed_attempt'], name: 'idx_ip_lockout')]
class AccountLockout
{
    // Configuration du verrouillage progressif (en minutes)
    private const LOCKOUT_DURATIONS = [5, 15, 60, 1440]; // 5min, 15min, 1h, 24h
    private const MAX_ATTEMPTS_BEFORE_LOCKOUT = 5;
    private const RESET_WINDOW_HOURS = 24; // Réinitialise le compteur après 24h sans échec

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: 'integer')]
    private int $failedAttempts = 0;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $lockedUntil = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $lastFailedAttempt;

    #[ORM\Column(type: 'string', length: 45, nullable: true)]
    private ?string $lastIpAddress = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->createdAt = new \DateTime();
        $this->lastFailedAttempt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getFailedAttempts(): int
    {
        return $this->failedAttempts;
    }

    public function getLockedUntil(): ?\DateTimeInterface
    {
        return $this->lockedUntil;
    }

    public function getLastFailedAttempt(): \DateTimeInterface
    {
        return $this->lastFailedAttempt;
    }

    public function getLastIpAddress(): ?string
    {
        return $this->lastIpAddress;
    }

    /**
     * Vérifie si le compte est actuellement verrouillé
     */
    public function isLocked(): bool
    {
        if ($this->lockedUntil === null) {
            return false;
        }

        return $this->lockedUntil > new \DateTime();
    }

    /**
     * Retourne le temps restant avant déverrouillage (en secondes)
     */
    public function getRemainingLockTime(): int
    {
        if (!$this->isLocked()) {
            return 0;
        }

        return $this->lockedUntil->getTimestamp() - (new \DateTime())->getTimestamp();
    }

    /**
     * Retourne le temps restant formaté
     */
    public function getRemainingLockTimeFormatted(): string
    {
        $seconds = $this->getRemainingLockTime();
        if ($seconds <= 0) {
            return '0 seconds';
        }

        $minutes = floor($seconds / 60);
        $hours = floor($minutes / 60);

        if ($hours > 0) {
            return sprintf('%d hour(s) %d minute(s)', $hours, $minutes % 60);
        } elseif ($minutes > 0) {
            return sprintf('%d minute(s)', $minutes);
        } else {
            return sprintf('%d seconds', $seconds);
        }
    }

    /**
     * Incrémente le compteur d'échecs et applique le verrouillage si nécessaire
     */
    public function incrementFailedAttempts(?string $ipAddress = null): self
    {
        // Vérifie si on doit réinitialiser le compteur (24h sans échec)
        $resetThreshold = (new \DateTime())->modify('-' . self::RESET_WINDOW_HOURS . ' hours');
        if ($this->lastFailedAttempt < $resetThreshold) {
            $this->failedAttempts = 0;
            $this->lockedUntil = null;
        }

        $this->failedAttempts++;
        $this->lastFailedAttempt = new \DateTime();
        $this->lastIpAddress = $ipAddress;
        $this->updatedAt = new \DateTime();

        // Applique le verrouillage si le seuil est atteint
        if ($this->failedAttempts >= self::MAX_ATTEMPTS_BEFORE_LOCKOUT) {
            $lockoutIndex = min(
                (int) floor(($this->failedAttempts - 1) / self::MAX_ATTEMPTS_BEFORE_LOCKOUT),
                \count(self::LOCKOUT_DURATIONS) - 1
            );
            $duration = self::LOCKOUT_DURATIONS[$lockoutIndex];
            $this->lockedUntil = (new \DateTime())->modify("+{$duration} minutes");
        }

        return $this;
    }

    /**
     * Réinitialise le compteur après une connexion réussie
     */
    public function resetLockout(): self
    {
        $this->failedAttempts = 0;
        $this->lockedUntil = null;
        $this->updatedAt = new \DateTime();

        return $this;
    }

    /**
     * Vérifie si la prochaine tentative entraînera un verrouillage
     */
    public function willLockOnNextAttempt(): bool
    {
        return ($this->failedAttempts + 1) % self::MAX_ATTEMPTS_BEFORE_LOCKOUT === 0;
    }

    /**
     * Retourne le nombre de tentatives restantes avant verrouillage
     */
    public function getAttemptsRemainingBeforeLockout(): int
    {
        $remaining = self::MAX_ATTEMPTS_BEFORE_LOCKOUT - ($this->failedAttempts % self::MAX_ATTEMPTS_BEFORE_LOCKOUT);
        return $remaining === self::MAX_ATTEMPTS_BEFORE_LOCKOUT ? 0 : $remaining;
    }
}
