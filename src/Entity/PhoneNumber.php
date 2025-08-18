<?php

namespace App\Entity;

use App\Repository\PhoneNumberRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: PhoneNumberRepository::class)]
class PhoneNumber
{
    #[ORM\ManyToOne(targetEntity: Contact::class, inversedBy: 'phones')]
    private ?Contact $contact = null;

    #[ORM\ManyToOne(targetEntity: Company::class, inversedBy: 'phones')]
    private ?Company $company = null;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['contact:edit', 'contact:list', 'deal:info', 'deal:edit', 'contact:info',  'company:list', 'company:info', 'deal:info'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['contact:edit', 'contact:list', 'deal:info', 'deal:edit', 'contact:info',  'company:list', 'company:info', 'deal:info'])]
    private ?string $number = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['contact:edit', 'contact:list', 'deal:info', 'deal:edit', 'contact:info',  'company:list', 'company:info', 'deal:info'])]
    private ?string $type = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(string $number): static
    {
        $this->number = $number;

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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }
}
