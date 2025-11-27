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
    private ?string $godfather = null;

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
}
