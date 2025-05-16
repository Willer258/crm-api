<?php

namespace App\Entity;

use App\Repository\PropertyRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: PropertyRepository::class)]
class Property
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['contact:edit' , 'contact:list' , 'company:edit' , 'company:list','deal:info' , 'deal:edit'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['contact:edit', 'contact:list' , 'company:edit' , 'company:list','deal:info' , 'deal:edit'])]
    private ?string $value = null;

    #[ORM\ManyToOne(inversedBy: 'properties')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Contact $contact = null;

    #[ORM\ManyToOne(inversedBy: 'properties')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Company $company = null;

    #[ORM\ManyToOne(inversedBy: 'properties')]
    #[Groups(['contact:edit', 'contact:list' , 'company:edit' , 'company:list','deal:info' , 'deal:edit'])]
    private ?PropertyModel $propertyModel = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(string $value): static
    {
        $this->value = $value;

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

    public function getPropertyModel(): ?PropertyModel
    {
        return $this->propertyModel;
    }

    public function setPropertyModel(?PropertyModel $propertyModel): static
    {
        $this->propertyModel = $propertyModel;

        return $this;
    }
}
