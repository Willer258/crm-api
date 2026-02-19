<?php

namespace App\Entity;

use App\Repository\EmailOtpRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * OTP pour vérification d'email (code à 6 chiffres)
 */
#[ORM\Entity(repositoryClass: EmailOtpRepository::class)]
#[ORM\Table(name: 'email_otps')]
#[ORM\Index(columns: ['email', 'created_at'], name: 'idx_email_otp_lookup')]
class EmailOtp
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 6)]
    private string $code;

    #[ORM\Column(type: 'string', length: 180)]
    private string $email;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $expiresAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'boolean')]
    private bool $isUsed = false;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $usedAt = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $attempts = 0;

    #[ORM\Column(type: 'string', length: 20)]
    private string $purpose; // 'email_verification', 'password_reset', 'login'

    #[ORM\Column(type: 'string', length: 45, nullable: true)]
    private ?string $ipAddress = null;

    public function __construct(string $email, string $purpose = 'email_verification', ?User $user = null, ?string $ipAddress = null)
    {
        $this->email = $email;
        $this->purpose = $purpose;
        $this->user = $user;
        $this->ipAddress = $ipAddress;
        $this->code = $this->generateCode();
        $this->createdAt = new \DateTime();
        // Expire dans 10 minutes
        $this->expiresAt = (new \DateTime())->modify('+10 minutes');
    }

    /**
     * Génère un code OTP à 6 chiffres
     */
    private function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getExpiresAt(): \DateTimeInterface
    {
        return $this->expiresAt;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function isUsed(): bool
    {
        return $this->isUsed;
    }

    public function markAsUsed(): self
    {
        $this->isUsed = true;
        $this->usedAt = new \DateTime();
        return $this;
    }

    public function getUsedAt(): ?\DateTimeInterface
    {
        return $this->usedAt;
    }

    public function getAttempts(): int
    {
        return $this->attempts;
    }

    public function incrementAttempts(): self
    {
        $this->attempts++;
        return $this;
    }

    public function getPurpose(): string
    {
        return $this->purpose;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt < new \DateTime();
    }

    public function isValid(): bool
    {
        return !$this->isExpired() && !$this->isUsed && $this->attempts < 5;
    }

    /**
     * Vérifie si le code fourni correspond
     */
    public function verify(string $code): bool
    {
        $this->incrementAttempts();

        if (!$this->isValid()) {
            return false;
        }

        return $this->code === $code;
    }
}
