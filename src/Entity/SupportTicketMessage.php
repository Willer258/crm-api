<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(name: 'support_ticket_message')]
class SupportTicketMessage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['ticket:detail'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: SupportTicket::class, inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: false)]
    private SupportTicket $ticket;

    #[ORM\Column(type: 'text')]
    #[Groups(['ticket:detail'])]
    private string $message;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['ticket:detail'])]
    private bool $isFromAgent = false;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['ticket:detail'])]
    private ?string $authorName = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['ticket:detail'])]
    private ?string $authorEmail = null;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['ticket:detail'])]
    private bool $isInternal = false;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['ticket:detail'])]
    private ?array $attachments = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['ticket:detail'])]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // Getters and Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTicket(): SupportTicket
    {
        return $this->ticket;
    }

    public function setTicket(SupportTicket $ticket): self
    {
        $this->ticket = $ticket;
        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;
        return $this;
    }

    public function isFromAgent(): bool
    {
        return $this->isFromAgent;
    }

    public function setIsFromAgent(bool $isFromAgent): self
    {
        $this->isFromAgent = $isFromAgent;
        return $this;
    }

    public function getAuthorName(): ?string
    {
        return $this->authorName;
    }

    public function setAuthorName(?string $authorName): self
    {
        $this->authorName = $authorName;
        return $this;
    }

    public function getAuthorEmail(): ?string
    {
        return $this->authorEmail;
    }

    public function setAuthorEmail(?string $authorEmail): self
    {
        $this->authorEmail = $authorEmail;
        return $this;
    }

    public function isInternal(): bool
    {
        return $this->isInternal;
    }

    public function setIsInternal(bool $isInternal): self
    {
        $this->isInternal = $isInternal;
        return $this;
    }

    public function getAttachments(): ?array
    {
        return $this->attachments;
    }

    public function setAttachments(?array $attachments): self
    {
        $this->attachments = $attachments;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
