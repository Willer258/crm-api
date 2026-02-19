<?php

namespace App\Entity;

use App\Repository\OAuthConnectionRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Connexion OAuth pour l'authentification via fournisseurs externes
 *
 * Permet aux utilisateurs de se connecter via Google, GitHub, ou un serveur Keycloak.
 * Un utilisateur peut avoir plusieurs connexions OAuth à différents fournisseurs.
 */
#[ORM\Entity(repositoryClass: OAuthConnectionRepository::class)]
#[ORM\Table(name: 'oauth_connections')]
#[ORM\Index(columns: ['provider', 'provider_user_id'], name: 'idx_oauth_provider_user')]
#[ORM\Index(columns: ['user_id'], name: 'idx_oauth_user')]
#[ORM\UniqueConstraint(name: 'unique_oauth_connection', columns: ['provider', 'provider_user_id'])]
class OAuthConnection
{
    public const PROVIDER_GOOGLE = 'google';
    public const PROVIDER_GITHUB = 'github';
    public const PROVIDER_KEYCLOAK = 'keycloak';

    public const VALID_PROVIDERS = [
        self::PROVIDER_GOOGLE,
        self::PROVIDER_GITHUB,
        self::PROVIDER_KEYCLOAK,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'oauthConnections')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    /**
     * Provider name (google, github, keycloak)
     */
    #[ORM\Column(type: 'string', length: 50)]
    private string $provider;

    /**
     * User ID from the OAuth provider
     */
    #[ORM\Column(type: 'string', length: 255)]
    private string $providerUserId;

    /**
     * Email from the OAuth provider (for reference/display)
     */
    #[ORM\Column(type: 'string', length: 180, nullable: true)]
    private ?string $providerEmail = null;

    /**
     * OAuth access token (encrypted in production)
     */
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $accessToken = null;

    /**
     * OAuth refresh token (encrypted in production)
     */
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $refreshToken = null;

    /**
     * When the access token expires
     */
    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $tokenExpiresAt = null;

    /**
     * When the OAuth connection was first created
     */
    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $connectedAt;

    /**
     * When the OAuth connection was last used to authenticate
     */
    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $lastUsedAt = null;

    /**
     * Additional data from the provider (profile info, etc.)
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $providerData = null;

    public function __construct(User $user, string $provider, string $providerUserId)
    {
        if (!in_array($provider, self::VALID_PROVIDERS, true)) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid OAuth provider "%s". Valid providers: %s',
                $provider,
                implode(', ', self::VALID_PROVIDERS)
            ));
        }

        $this->user = $user;
        $this->provider = $provider;
        $this->providerUserId = $providerUserId;
        $this->connectedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function getProviderUserId(): string
    {
        return $this->providerUserId;
    }

    public function getProviderEmail(): ?string
    {
        return $this->providerEmail;
    }

    public function setProviderEmail(?string $providerEmail): self
    {
        $this->providerEmail = $providerEmail;
        return $this;
    }

    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    public function setAccessToken(?string $accessToken): self
    {
        $this->accessToken = $accessToken;
        return $this;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function setRefreshToken(?string $refreshToken): self
    {
        $this->refreshToken = $refreshToken;
        return $this;
    }

    public function getTokenExpiresAt(): ?\DateTimeInterface
    {
        return $this->tokenExpiresAt;
    }

    public function setTokenExpiresAt(?\DateTimeInterface $tokenExpiresAt): self
    {
        $this->tokenExpiresAt = $tokenExpiresAt;
        return $this;
    }

    public function getConnectedAt(): \DateTimeInterface
    {
        return $this->connectedAt;
    }

    public function getLastUsedAt(): ?\DateTimeInterface
    {
        return $this->lastUsedAt;
    }

    public function markAsUsed(): self
    {
        $this->lastUsedAt = new \DateTime();
        return $this;
    }

    public function getProviderData(): ?array
    {
        return $this->providerData;
    }

    public function setProviderData(?array $providerData): self
    {
        $this->providerData = $providerData;
        return $this;
    }

    /**
     * Check if the access token is expired
     */
    public function isTokenExpired(): bool
    {
        if (!$this->tokenExpiresAt) {
            return true;
        }

        return $this->tokenExpiresAt < new \DateTime();
    }

    /**
     * Check if we have a valid access token
     */
    public function hasValidAccessToken(): bool
    {
        return $this->accessToken && !$this->isTokenExpired();
    }

    /**
     * Update tokens from OAuth response
     */
    public function updateTokens(
        string $accessToken,
        ?string $refreshToken = null,
        ?int $expiresIn = null
    ): self {
        $this->accessToken = $accessToken;

        if ($refreshToken) {
            $this->refreshToken = $refreshToken;
        }

        if ($expiresIn) {
            $this->tokenExpiresAt = (new \DateTime())->modify("+{$expiresIn} seconds");
        }

        return $this;
    }

    /**
     * Get display name for the provider
     */
    public function getProviderDisplayName(): string
    {
        return match ($this->provider) {
            self::PROVIDER_GOOGLE => 'Google',
            self::PROVIDER_GITHUB => 'GitHub',
            self::PROVIDER_KEYCLOAK => 'Keycloak SSO',
            default => ucfirst($this->provider),
        };
    }
}
