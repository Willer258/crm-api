<?php

namespace App\Entity;

use App\Repository\MessageRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: MessageRepository::class)]
#[ORM\Table(name: 'message')]
#[ORM\Index(columns: ['channel'], name: 'idx_message_channel')]
#[ORM\Index(columns: ['direction'], name: 'idx_message_direction')]
#[ORM\Index(columns: ['status'], name: 'idx_message_status')]
#[ORM\Index(columns: ['created_at'], name: 'idx_message_created_at')]
#[ORM\HasLifecycleCallbacks]
class Message
{
    public const CHANNEL_WHATSAPP = 'whatsapp';
    public const CHANNEL_EMAIL    = 'email';

    public const DIRECTION_IN  = 'in';
    public const DIRECTION_OUT = 'out';

    public const STATUS_PENDING   = 'pending';
    public const STATUS_SENT      = 'sent';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_READ      = 'read';
    public const STATUS_FAILED    = 'failed';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['message:list', 'message:detail', 'thread:detail'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Workspace::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Workspace $workspace = null;

    #[ORM\ManyToOne(targetEntity: MessageThread::class, inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['message:list', 'message:detail'])]
    private ?MessageThread $thread = null;

    #[ORM\ManyToOne(targetEntity: Contact::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['message:list', 'message:detail', 'thread:detail'])]
    private ?Contact $contact = null;

    /**
     * Canal de communication : whatsapp | email
     */
    #[ORM\Column(type: 'string', length: 20)]
    #[Groups(['message:list', 'message:detail', 'thread:detail'])]
    private string $channel = self::CHANNEL_WHATSAPP;

    /**
     * Sens du message : in (reçu) | out (envoyé)
     */
    #[ORM\Column(type: 'string', length: 10)]
    #[Groups(['message:list', 'message:detail', 'thread:detail'])]
    private string $direction = self::DIRECTION_IN;

    /**
     * Expéditeur (numéro WhatsApp ou email)
     */
    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['message:list', 'message:detail', 'thread:detail'])]
    private string $fromAddress = '';

    /**
     * Destinataire (numéro WhatsApp ou email)
     */
    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['message:list', 'message:detail', 'thread:detail'])]
    private string $toAddress = '';

    /**
     * Objet du message (Email uniquement)
     */
    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    #[Groups(['message:list', 'message:detail', 'thread:detail'])]
    private ?string $subject = null;

    /**
     * Contenu du message
     */
    #[ORM\Column(type: 'text')]
    #[Groups(['message:detail', 'thread:detail'])]
    private string $content = '';

    /**
     * Statut : pending | sent | delivered | read | failed
     */
    #[ORM\Column(type: 'string', length: 20, options: ['default' => 'pending'])]
    #[Groups(['message:list', 'message:detail', 'thread:detail'])]
    private string $status = self::STATUS_PENDING;

    /**
     * Métadonnées : message_id WA, headers email, etc.
     */
    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['message:detail'])]
    private ?array $metadata = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['message:detail', 'thread:detail'])]
    private ?\DateTimeImmutable $readAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['message:list', 'message:detail', 'thread:detail'])]
    private ?\DateTimeImmutable $sentAt = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    #[Groups(['message:list', 'message:detail', 'thread:detail'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['message:detail'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // Getters & Setters

    public function getId(): ?int
    {
        return $this->id;
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

    public function getThread(): ?MessageThread
    {
        return $this->thread;
    }

    public function setThread(?MessageThread $thread): static
    {
        $this->thread = $thread;
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

    public function getChannel(): string
    {
        return $this->channel;
    }

    public function setChannel(string $channel): static
    {
        $this->channel = $channel;
        return $this;
    }

    public function getDirection(): string
    {
        return $this->direction;
    }

    public function setDirection(string $direction): static
    {
        $this->direction = $direction;
        return $this;
    }

    public function getFromAddress(): string
    {
        return $this->fromAddress;
    }

    public function setFromAddress(string $fromAddress): static
    {
        $this->fromAddress = $fromAddress;
        return $this;
    }

    public function getToAddress(): string
    {
        return $this->toAddress;
    }

    public function setToAddress(string $toAddress): static
    {
        $this->toAddress = $toAddress;
        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject): static
    {
        $this->subject = $subject;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): static
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function getReadAt(): ?\DateTimeImmutable
    {
        return $this->readAt;
    }

    public function setReadAt(?\DateTimeImmutable $readAt): static
    {
        $this->readAt = $readAt;
        return $this;
    }

    public function getSentAt(): ?\DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function setSentAt(?\DateTimeImmutable $sentAt): static
    {
        $this->sentAt = $sentAt;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function isRead(): bool
    {
        return $this->readAt !== null || $this->status === self::STATUS_READ;
    }

    public function getContentPreview(int $length = 80): string
    {
        return mb_strlen($this->content) > $length
            ? mb_substr($this->content, 0, $length) . '…'
            : $this->content;
    }
}
