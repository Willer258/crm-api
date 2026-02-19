<?php

namespace App\Entity;

use App\Repository\RefreshTokenRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Refresh Token pour renouveler les JWT expirés
 *
 * SECURITE: Le token est stocké sous forme de hash SHA-256 en base de données.
 * Le token en clair n'est disponible qu'immédiatement après la création (via getPlainToken()).
 */
#[ORM\Entity(repositoryClass: RefreshTokenRepository::class)]
#[ORM\Table(name: 'refresh_tokens')]
#[ORM\Index(columns: ['token_hash'], name: 'idx_refresh_token_hash')]
#[ORM\Index(columns: ['user_id', 'is_revoked'], name: 'idx_user_active_tokens')]
class RefreshToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Hash SHA-256 du token (64 caractères hex)
     * Le token en clair n'est JAMAIS stocké en base de données
     */
    #[ORM\Column(type: 'string', length: 64, unique: true)]
    private string $tokenHash;

    /**
     * Token en clair - NON PERSISTE en base de données
     * Disponible uniquement après la création de l'entité
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
    private bool $isRevoked = false;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column(type: 'string', length: 45, nullable: true)]
    private ?string $ipAddress = null;

    #[ORM\Column(type: 'string', length: 64, nullable: true)]
    private ?string $deviceFingerprint = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $deviceName = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $lastUsedAt = null;

    public function __construct(?User $user = null, ?string $ipAddress = null, ?string $userAgent = null)
    {
        if ($user) {
            $this->user = $user;
        }

        // Générer un token aléatoire de 128 caractères (64 bytes en hex)
        $this->plainToken = bin2hex(random_bytes(64));

        // Stocker uniquement le hash SHA-256 en base de données
        $this->tokenHash = self::hashToken($this->plainToken);

        $this->createdAt = new \DateTime();
        // Expire dans 14 jours (au lieu de 30 pour plus de sécurité)
        $this->expiresAt = (new \DateTime())->modify('+14 days');

        $this->ipAddress = $ipAddress;
        $this->userAgent = $userAgent;

        // Parser le device name depuis le User-Agent
        if ($userAgent) {
            $this->deviceName = self::parseDeviceName($userAgent);
        }
    }

    /**
     * Hash un token en clair avec SHA-256
     */
    public static function hashToken(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }

    /**
     * Vérifie si un token en clair correspond au hash stocké
     */
    public static function verifyToken(string $plainToken, string $storedHash): bool
    {
        return hash_equals($storedHash, self::hashToken($plainToken));
    }

    /**
     * Parse le nom du device depuis le User-Agent
     */
    private static function parseDeviceName(string $userAgent): string
    {
        // Détection basique du navigateur et OS
        $browser = 'Unknown Browser';
        $os = 'Unknown OS';

        // Détection du navigateur
        if (preg_match('/Firefox\/[\d.]+/i', $userAgent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/Chrome\/[\d.]+/i', $userAgent) && !preg_match('/Edg/i', $userAgent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/Safari\/[\d.]+/i', $userAgent) && !preg_match('/Chrome/i', $userAgent)) {
            $browser = 'Safari';
        } elseif (preg_match('/Edg\/[\d.]+/i', $userAgent)) {
            $browser = 'Edge';
        } elseif (preg_match('/MSIE|Trident/i', $userAgent)) {
            $browser = 'Internet Explorer';
        }

        // Détection de l'OS
        if (preg_match('/Windows NT/i', $userAgent)) {
            $os = 'Windows';
        } elseif (preg_match('/Mac OS X/i', $userAgent)) {
            $os = 'macOS';
        } elseif (preg_match('/Linux/i', $userAgent)) {
            $os = 'Linux';
        } elseif (preg_match('/iPhone|iPad/i', $userAgent)) {
            $os = 'iOS';
        } elseif (preg_match('/Android/i', $userAgent)) {
            $os = 'Android';
        }

        return "{$browser} on {$os}";
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Retourne le token en clair (uniquement disponible après création)
     *
     * ATTENTION: Cette méthode retourne null si le token a été chargé depuis la DB
     * car le token en clair n'est jamais persisté.
     */
    public function getPlainToken(): ?string
    {
        return $this->plainToken;
    }

    /**
     * Retourne le hash du token (stocké en DB)
     */
    public function getTokenHash(): string
    {
        return $this->tokenHash;
    }

    /**
     * @deprecated Use getPlainToken() for new tokens or getTokenHash() for stored hash
     */
    public function getToken(): string
    {
        // Pour la rétrocompatibilité, retourne le plain token si disponible, sinon le hash
        return $this->plainToken ?? $this->tokenHash;
    }

    /**
     * @deprecated Tokens should only be set via constructor
     */
    public function setToken(string $token): self
    {
        // Pour la rétrocompatibilité lors de la migration
        $this->tokenHash = $token;
        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getExpiresAt(): \DateTimeInterface
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(\DateTimeInterface $expiresAt): self
    {
        $this->expiresAt = $expiresAt;
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function isRevoked(): bool
    {
        return $this->isRevoked;
    }

    public function setIsRevoked(bool $isRevoked): self
    {
        $this->isRevoked = $isRevoked;
        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): self
    {
        $this->userAgent = $userAgent;
        if ($userAgent) {
            $this->deviceName = self::parseDeviceName($userAgent);
        }
        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): self
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }

    public function getDeviceFingerprint(): ?string
    {
        return $this->deviceFingerprint;
    }

    public function setDeviceFingerprint(?string $deviceFingerprint): self
    {
        $this->deviceFingerprint = $deviceFingerprint;
        return $this;
    }

    public function getDeviceName(): ?string
    {
        return $this->deviceName;
    }

    public function setDeviceName(?string $deviceName): self
    {
        $this->deviceName = $deviceName;
        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): self
    {
        $this->location = $location;
        return $this;
    }

    public function getLastUsedAt(): ?\DateTimeInterface
    {
        return $this->lastUsedAt;
    }

    public function setLastUsedAt(?\DateTimeInterface $lastUsedAt): self
    {
        $this->lastUsedAt = $lastUsedAt;
        return $this;
    }

    /**
     * Met à jour la date de dernière utilisation
     */
    public function markAsUsed(): self
    {
        $this->lastUsedAt = new \DateTime();
        return $this;
    }

    /**
     * Vérifie si le token est expiré
     */
    public function isExpired(): bool
    {
        return $this->expiresAt < new \DateTime();
    }

    /**
     * Vérifie si le token est valide (non expiré et non révoqué)
     */
    public function isValid(): bool
    {
        return !$this->isExpired() && !$this->isRevoked;
    }

    /**
     * Révoque le token
     */
    public function revoke(): self
    {
        $this->isRevoked = true;
        return $this;
    }
}
