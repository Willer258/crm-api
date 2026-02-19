<?php

namespace App\Entity;

use App\Repository\MessageThreadRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: MessageThreadRepository::class)]
#[ORM\Table(name: 'message_thread')]
#[ORM\Index(columns: ['channel'], name: 'idx_thread_channel')]
#[ORM\Index(columns: ['last_message_at'], name: 'idx_thread_last_message')]
#[ORM\HasLifecycleCallbacks]
class MessageThread
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['thread:list', 'thread:detail', 'message:list'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Workspace::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['thread:list', 'thread:detail'])]
    private ?Workspace $workspace = null;

    #[ORM\ManyToOne(targetEntity: Contact::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['thread:list', 'thread:detail'])]
    private ?Contact $contact = null;

    #[ORM\Column(type: 'string', length: 20)]
    #[Groups(['thread:list', 'thread:detail', 'message:list'])]
    private string $channel = 'whatsapp'; // whatsapp | email

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['thread:list', 'thread:detail'])]
    private ?string $subject = null; // Pour email uniquement

    /**
     * Adresse de contact : numéro WhatsApp ou email
     */
    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['thread:list', 'thread:detail'])]
    private string $contactAddress = '';

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Groups(['thread:list', 'thread:detail'])]
    private int $unreadCount = 0;

    #[ORM\Column(type: 'string', length: 20, options: ['default' => 'open'])]
    #[Groups(['thread:list', 'thread:detail'])]
    private string $status = 'open'; // open | archived

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['thread:list', 'thread:detail'])]
    private ?\DateTimeImmutable $lastMessageAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['thread:list', 'thread:detail'])]
    private ?string $lastMessagePreview = null;

    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'thread', cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    #[Groups(['thread:detail'])]
    private Collection $messages;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['thread:list', 'thread:detail'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['thread:list', 'thread:detail'])]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->messages = new ArrayCollection();
    }

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

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject): static
    {
        $this->subject = $subject;
        return $this;
    }

    public function getContactAddress(): string
    {
        return $this->contactAddress;
    }

    public function setContactAddress(string $contactAddress): static
    {
        $this->contactAddress = $contactAddress;
        return $this;
    }

    public function getUnreadCount(): int
    {
        return $this->unreadCount;
    }

    public function setUnreadCount(int $unreadCount): static
    {
        $this->unreadCount = max(0, $unreadCount);
        return $this;
    }

    public function incrementUnreadCount(): static
    {
        $this->unreadCount++;
        return $this;
    }

    public function resetUnreadCount(): static
    {
        $this->unreadCount = 0;
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

    public function getLastMessageAt(): ?\DateTimeImmutable
    {
        return $this->lastMessageAt;
    }

    public function setLastMessageAt(?\DateTimeImmutable $lastMessageAt): static
    {
        $this->lastMessageAt = $lastMessageAt;
        return $this;
    }

    public function getLastMessagePreview(): ?string
    {
        return $this->lastMessagePreview;
    }

    public function setLastMessagePreview(?string $lastMessagePreview): static
    {
        $this->lastMessagePreview = $lastMessagePreview !== null
            ? mb_substr($lastMessagePreview, 0, 120)
            : null;
        return $this;
    }

    /**
     * @return Collection<int, Message>
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(Message $message): static
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setThread($this);
        }
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
}
