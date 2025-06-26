<?php

namespace App\Entity;

use App\Repository\MailRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: MailRepository::class)]
class Mail
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['contact:edit' , 'contact:list' , 'company:edit' , 'company:list','deal:info' , 'deal:edit' , 'contact:info', 'company:info' , 'company:list'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['contact:edit', 'contact:list', 'deal:info' , 'deal:edit' , 'contact:info', 'company:info' , 'company:list'])]
    private ?string $email = null;

    #[ORM\ManyToOne(inversedBy: 'mails')]
    private ?Contact $contact = null;

    #[ORM\ManyToOne(inversedBy: 'mails')]
    private ?Company $company = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['contact:edit', 'contact:list', 'deal:info' , 'deal:edit' , 'contact:info', 'company:info' , 'company:list'])]
    private ?string $type = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

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
