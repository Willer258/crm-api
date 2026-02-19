<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Lexik\Bundle\JWTAuthenticationBundle\Security\User\JWTUserInterface;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User  implements UserInterface, PasswordAuthenticatedUserInterface, JWTUserInterface
{


    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    /**
     * @var UuidInterface
     */
    #[ORM\Column(type: 'uuid', length: 255, unique: true)]
    private $uuid;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private $email;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private $code;

    #[ORM\Column(type: 'json')]
    private $roles = [];

    #[ORM\Column(type: 'string', length: 255)]
    private string $password;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $firstname = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lastname = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isActive = false;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $godfather = null;

    #[ORM\ManyToOne(targetEntity: Workspace::class, inversedBy: 'users')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Workspace $currentWorkspace = null;

    /**
     * @var Collection<int, WorkspaceMember>
     */
    #[ORM\OneToMany(targetEntity: WorkspaceMember::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private Collection $workspaceMemberships;

    /**
     * @var Collection<int, OAuthConnection>
     */
    #[ORM\OneToMany(targetEntity: OAuthConnection::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private Collection $oauthConnections;

    /**
     * Two-factor authentication settings
     */
    #[ORM\OneToOne(targetEntity: TwoFactorAuth::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private ?TwoFactorAuth $twoFactorAuth = null;

    // public function __construct($username, array $roles)
    // {

    //     $this->email = $username;
    //     $this->roles = $roles;
    //     $this->todos = new ArrayCollection();
    // }
    public function __construct($username = null, array $payload = [])
    {

        $this->email = $username;
        if (isset($payload['roles'])) {
            $this->roles = $payload['roles'];
        }
        if (isset($payload['uuid'])) {
            $this->uuid = $payload['uuid'];
        }
        if (isset($payload['godfather'])) {
            $this->godfather = $payload['godfather'];
        }
        if (isset($payload['code'])) {
            $this->code = $payload['code'];
        }
        // Initialiser password avec une valeur par défaut vide pour les users JWT
        $this->password = $payload['password'] ?? '';
        $this->createdAt = new \DateTime();
        $this->workspaceMemberships = new ArrayCollection();
        $this->oauthConnections = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string)$this->email;
    }

    /**
     * @deprecated since Symfony 5.3, use getUserIdentifier instead
     */
    public function getUsername(): string
    {
        return (string)$this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * Creates a new instance from a given JWT payload.
     *
     * @param string $username
     *
     * @return JWTUserInterface
     */
    public static function createFromPayload($username, array $payload)
    {
        return new self($username, $payload);
    }
    /**
     * Get the value of uuid
     *
     * @return  UuidInterface
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * Set the value of uuid
     *
     * @param  UuidInterface  $uuid
     *
     * @return  self
     */
    public function setUuid(UuidInterface $uuid)
    {
        $this->uuid = $uuid;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCode(): mixed
    {
        return $this->code;
    }

    /**
     * @param mixed $code
     */
    public function setCode(mixed $code): void
    {
        $this->code = $code;
    }

    public function getGodfather(): ?string
    {
        return $this->godfather;
    }

    public function setGodfather(?string $godfather): self
    {
        $this->godfather = $godfather;

        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(?string $firstname): self
    {
        $this->firstname = $firstname;
        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(?string $lastname): self
    {
        $this->lastname = $lastname;
        return $this;
    }

    public function getIsActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getCurrentWorkspace(): ?Workspace
    {
        return $this->currentWorkspace;
    }

    public function setCurrentWorkspace(?Workspace $currentWorkspace): self
    {
        $this->currentWorkspace = $currentWorkspace;
        return $this;
    }

    /**
     * @return Collection<int, WorkspaceMember>
     */
    public function getWorkspaceMemberships(): Collection
    {
        return $this->workspaceMemberships;
    }

    public function addWorkspaceMembership(WorkspaceMember $workspaceMembership): self
    {
        if (!$this->workspaceMemberships->contains($workspaceMembership)) {
            $this->workspaceMemberships->add($workspaceMembership);
            $workspaceMembership->setUser($this);
        }

        return $this;
    }

    public function removeWorkspaceMembership(WorkspaceMember $workspaceMembership): self
    {
        if ($this->workspaceMemberships->removeElement($workspaceMembership)) {
            // set the owning side to null (unless already changed)
            if ($workspaceMembership->getUser() === $this) {
                $workspaceMembership->setUser(null);
            }
        }

        return $this;
    }

    /**
     * Get all workspaces the user belongs to
     */
    public function getWorkspaces(): array
    {
        return $this->workspaceMemberships
            ->filter(fn(WorkspaceMember $m) => $m->isActive())
            ->map(fn(WorkspaceMember $m) => $m->getWorkspace())
            ->toArray();
    }

    /**
     * @return Collection<int, OAuthConnection>
     */
    public function getOauthConnections(): Collection
    {
        return $this->oauthConnections;
    }

    public function addOauthConnection(OAuthConnection $oauthConnection): self
    {
        if (!$this->oauthConnections->contains($oauthConnection)) {
            $this->oauthConnections->add($oauthConnection);
        }

        return $this;
    }

    public function removeOauthConnection(OAuthConnection $oauthConnection): self
    {
        $this->oauthConnections->removeElement($oauthConnection);

        return $this;
    }

    /**
     * Check if user has a connection to a specific OAuth provider
     */
    public function hasOAuthProvider(string $provider): bool
    {
        foreach ($this->oauthConnections as $connection) {
            if ($connection->getProvider() === $provider) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get the 2FA settings for this user
     */
    public function getTwoFactorAuth(): ?TwoFactorAuth
    {
        return $this->twoFactorAuth;
    }

    public function setTwoFactorAuth(?TwoFactorAuth $twoFactorAuth): self
    {
        $this->twoFactorAuth = $twoFactorAuth;

        return $this;
    }

    /**
     * Check if 2FA is enabled for this user
     */
    public function isTwoFactorEnabled(): bool
    {
        return $this->twoFactorAuth !== null && $this->twoFactorAuth->isEnabled();
    }
}
