<?php

namespace App\Entity;

use App\Repository\NoteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Traits\UserObjectTrait;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: NoteRepository::class)]
class Note
{
    use UserObjectTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['note:edit', 'note:list' , 'contact:info', 'company:info' , 'activity:read'])]
    private ?int $id = null;


    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['note:edit', 'note:list' , 'contact:info', 'company:info' , 'activity:read'])]
    private ?string $content = null;

    #[ORM\ManyToOne(inversedBy: 'notes')]
    private ?Deal $deal = null;

    #[ORM\ManyToOne(inversedBy: 'notes')]
    private ?Activity $activity = null;

    #[ORM\ManyToOne(inversedBy: 'notes')]
    private ?Company $company = null;

    #[ORM\ManyToOne(inversedBy: 'notes')]
    private ?Contact $contact = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

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

    public function getActivity(): ?Activity
    {
        return $this->activity;
    }

    public function setActivity(?Activity $activity): static
    {
        $this->activity = $activity;

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

    public function getContact(): ?Contact
    {
        return $this->contact;
    }

    public function setContact(?Contact $contact): static
    {
        $this->contact = $contact;

        return $this;
    }
}
