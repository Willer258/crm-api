<?php

namespace App\Entity;

use App\Repository\FileRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: FileRepository::class)]
class Asset
{
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
}
