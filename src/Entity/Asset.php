<?php

namespace App\Entity;

use App\Repository\FileRepository;
use App\Traits\UserObjectTrait;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=false, hardDelete=true)
 */
#[ORM\Entity(repositoryClass: FileRepository::class)]
#[ORM\Index(columns: ['workspace_id'], name: 'idx_asset_workspace')]
#[ORM\HasLifecycleCallbacks]
class Asset
{
    use UserObjectTrait;

    // Types de fichiers supportés
    public const TYPE_IMAGE = 'image';
    public const TYPE_PDF = 'pdf';
    public const TYPE_DOC = 'doc';
    public const TYPE_XLS = 'xls';
    public const TYPE_OTHER = 'other';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['file:edit', 'file:list', 'contact:info', 'contact:edit', 'contact:list', 'deal:info', 'deal:edit', 'company:info'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['file:edit', 'file:list', 'contact:info', 'contact:edit', 'contact:list', 'deal:info', 'deal:edit', 'company:info'])]
    private ?string $src = null;

    #[ORM\Column(length: 255)]
    #[Groups(['file:edit', 'file:list', 'contact:info', 'contact:edit', 'contact:list', 'deal:info', 'deal:edit', 'company:info'])]
    private ?string $realName = null;

    #[Groups(['file:edit', 'file:list'])]
    private ?string $fullPath = null;

    #[ORM\ManyToOne(targetEntity: Workspace::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['file:list', 'file:edit'])]
    private ?Workspace $workspace = null;

    #[ORM\ManyToOne(inversedBy: 'files')]
    private ?Contact $contact = null;

    #[ORM\ManyToOne(inversedBy: 'files')]
    private ?Company $company = null;

    #[ORM\ManyToOne(inversedBy: 'files')]
    private ?Deal $deal = null;

    #[ORM\Column(length: 255)]
    #[Groups(['file:edit', 'file:list'])]
    private ?string $type = null;

    #[ORM\Column(length: 255)]
    #[Groups(['file:edit', 'file:list', 'contact:info', 'contact:edit', 'contact:list', 'deal:info', 'deal:edit', 'company:info'])]
    private ?string $name = null;

    public function __construct()
    {
        $this->uuid = Uuid::uuid4();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSrc(): ?string
    {
        return $this->src;
    }

    public function setSrc(string $src): static
    {
        $this->src = $src;

        return $this;
    }

    public function getContact(): ?Contact
    {
        return $this->contact;
    }

    public function setContact(?Contact $contact): static
    {
        $this->contact = $contact;

        return $this;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): static
    {
        $this->company = $company;

        return $this;
    }

    public function getDeal(): ?Deal
    {
        return $this->deal;
    }

    public function setDeal(?Deal $deal): static
    {
        $this->deal = $deal;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getWorkspace(): ?Workspace
    {
        return $this->workspace;
    }

    public function setWorkspace(?Workspace $workspace): static
    {
        $this->workspace = $workspace;

        return $this;
    }

    public function getRealName(): ?string
    {
        return $this->realName;
    }

    public function setRealName(string $realName): static
    {
        $this->realName = $realName;

        return $this;
    }

    public function getFullPath(): ?string
    {
        return $this->fullPath;
    }

    public function setFullPath(?string $fullPath): static
    {
        $this->fullPath = $fullPath;

        return $this;
    }

    public function getUuid(): ?UuidInterface
    {
        return $this->uuid;
    }

    public function setUuid(UuidInterface $uuid): static
    {
        $this->uuid = $uuid;

        return $this;
    }
}
