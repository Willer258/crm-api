<?php

namespace App\Entity;

use App\Repository\MagicLinkRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Token pour l'authentification sans mot de passe (Magic Link)
 *
 * Permet aux utilisateurs de se connecter via un lien envoyé par email.
 * Tokens à usage unique avec expiration courte (15 minutes).
 */
#[ORM\Entity(repositoryClass: MagicLinkRepository::class)]
#[ORM\Table(name: 'magic_links')]
#[ORM\Index(columns: ['token_hash'], name: 'idx_magic_link_token')]
#[ORM\Index(columns: ['user_id', 'is_used'], name: 'idx_magic_link_user')]
#[ORM\Index(columns: ['expires_at'], name: 'idx_magic_link_expires')]
class MagicLink
{
    private const EXPIRY_MINUTES = 15;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Hash SHA-256 du token (64 caractères)
     * Le token en clair n'est jamais stocké en base
     */
    #[ORM\Column(type: 'string', length: 64, unique: true)]
    private string $tokenHash;

    /**
     * Token en clair - non persisté
     * Disponible uniquement après création
     */
    private ?string $plainToken = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $expiresAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'boolean')]
    private bool $isUsed = false;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $usedAt = null;

    #[ORM\Column(type: 'string', length: 45, nullable: true)]
    private ?string $ipAddress = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $userAgent = null;

    /**
     * IP qui a utilisé le lien (pour audit)
     */
    #[ORM\Column(type: 'string', length: 45, nullable: true)]
    private ?string $usedFromIp = null;

    public function __construct(User $user, ?string $ipAddress = null, ?string $userAgent = null)
    {
        $this->user = $user;
        $this->ipAddress = $ipAddress;
        $this->userAgent = $userAgent;

        // Génère un token aléatoire de 64 caractères (32 bytes en hex)
        $this->plainToken = bin2hex(random_bytes(32));

        // Stocke uniquement le hash
        $this->tokenHash = self::hashToken($this->plainToken);

        $this->createdAt = new \DateTime();
        $this->expiresAt = (new \DateTime())->modify('+' . self::EXPIRY_MINUTES . ' minutes');
    }

    /**
     * Hash un token en clair avec SHA-256
     */
    public static function hashToken(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Retourne le token en clair (uniquement après création)
     */
    public function getPlainToken(): ?string
    {
        return $this->plainToken;
    }

    public function getTokenHash(): string
    {
        return $this->tokenHash;
    }

    public function getUser(): User
    {
        return $this->user;
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

    public function getUsedAt(): ?\DateTimeInterface
    {
        return $this->usedAt;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function getUsedFromIp(): ?string
    {
        return $this->usedFromIp;
    }

    /**
     * Vérifie si le lien est expiré
     */
    public function isExpired(): bool
    {
        return $this->expiresAt < new \DateTime();
    }

    /**
     * Vérifie si le lien est valide (non utilisé et non expiré)
     */
    public function isValid(): bool
    {
        return !$this->isUsed && !$this->isExpired();
    }

    /**
     * Marque le lien comme utilisé
     */
    public function markAsUsed(?string $usedFromIp = null): self
    {
        $this->isUsed = true;
        $this->usedAt = new \DateTime();
        $this->usedFromIp = $usedFromIp;

        return $this;
    }

    /**
     * Retourne le temps restant avant expiration (en secondes)
     */
    public function getTimeRemaining(): int
    {
        $remaining = $this->expiresAt->getTimestamp() - (new \DateTime())->getTimestamp();
        return max(0, $remaining);
    }

    /**
     * Génère l'URL complète du magic link
     */
    public function generateUrl(string $baseUrl, string $locale = 'fr'): string
    {
        if (!$this->plainToken) {
            throw new \RuntimeException('Plain token is not available. URL can only be generated immediately after creation.');
        }

        return rtrim($baseUrl, '/') . '/' . $locale . '/magic-link/verify?token=' . urlencode($this->plainToken);
    }
}
