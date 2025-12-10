<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(name: 'support_ticket')]
#[ORM\Index(name: 'idx_tenant', columns: ['tenant_id'])]
#[ORM\Index(name: 'idx_status', columns: ['status'])]
#[ORM\Index(name: 'idx_priority', columns: ['priority'])]
class SupportTicket
{
    public const STATUS_OPEN = 'open';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_WAITING_CUSTOMER = 'waiting_customer';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_CLOSED = 'closed';

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    public const CATEGORY_TECHNICAL = 'technical';
    public const CATEGORY_BILLING = 'billing';
    public const CATEGORY_FEATURE_REQUEST = 'feature_request';
    public const CATEGORY_BUG = 'bug';
    public const CATEGORY_QUESTION = 'question';
    public const CATEGORY_OTHER = 'other';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['ticket:read'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 50, unique: true)]
    #[Groups(['ticket:read'])]
    private string $ticketNumber;

    #[ORM\Column(type: 'string', length: 100)]
    #[Groups(['ticket:read'])]
    private string $tenantId;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['ticket:read'])]
    private string $subject;

    #[ORM\Column(type: 'text')]
    #[Groups(['ticket:read', 'ticket:detail'])]
    private string $description;

    #[ORM\Column(type: 'string', length: 50)]
    #[Groups(['ticket:read'])]
    private string $category;

    #[ORM\Column(type: 'string', length: 20)]
    #[Groups(['ticket:read'])]
    private string $priority = self::PRIORITY_MEDIUM;

    #[ORM\Column(type: 'string', length: 30)]
    #[Groups(['ticket:read'])]
    private string $status = self::STATUS_OPEN;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['ticket:read'])]
    private string $requesterEmail;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['ticket:read'])]
    private ?string $requesterName = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['ticket:detail'])]
    private ?int $assignedToUserId = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['ticket:detail'])]
    private ?string $assignedToName = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['ticket:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['ticket:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['ticket:read'])]
    private ?\DateTimeImmutable $firstResponseAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['ticket:read'])]
    private ?\DateTimeImmutable $resolvedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['ticket:read'])]
    private ?\DateTimeImmutable $closedAt = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['ticket:detail'])]
    private ?array $tags = [];

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['ticket:detail'])]
    private ?array $metadata = null;

    #[ORM\OneToMany(mappedBy: 'ticket', targetEntity: SupportTicketMessage::class, cascade: ['persist', 'remove'])]
    #[Groups(['ticket:detail'])]
    private Collection $messages;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->ticketNumber = $this->generateTicketNumber();
        $this->messages = new ArrayCollection();
    }

    private function generateTicketNumber(): string
    {
        return 'TKT-' . strtoupper(substr(uniqid(), -8));
    }

    // Getters and Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTicketNumber(): string
    {
        return $this->ticketNumber;
    }

    public function getTenantId(): string
    {
        return $this->tenantId;
    }

    public function setTenantId(string $tenantId): self
    {
        $this->tenantId = $tenantId;
        return $this;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setCategory(string $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function getPriority(): string
    {
        return $this->priority;
    }

    public function setPriority(string $priority): self
    {
        $this->priority = $priority;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $oldStatus = $this->status;
        $this->status = $status;
        $this->updatedAt = new \DateTimeImmutable();

        if ($status === self::STATUS_RESOLVED && $oldStatus !== self::STATUS_RESOLVED) {
            $this->resolvedAt = new \DateTimeImmutable();
        }

        if ($status === self::STATUS_CLOSED && $oldStatus !== self::STATUS_CLOSED) {
            $this->closedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function getRequesterEmail(): string
    {
        return $this->requesterEmail;
    }

    public function setRequesterEmail(string $requesterEmail): self
    {
        $this->requesterEmail = $requesterEmail;
        return $this;
    }

    public function getRequesterName(): ?string
    {
        return $this->requesterName;
    }

    public function setRequesterName(?string $requesterName): self
    {
        $this->requesterName = $requesterName;
        return $this;
    }

    public function getAssignedToUserId(): ?int
    {
        return $this->assignedToUserId;
    }

    public function setAssignedToUserId(?int $assignedToUserId): self
    {
        $this->assignedToUserId = $assignedToUserId;
        return $this;
    }

    public function getAssignedToName(): ?string
    {
        return $this->assignedToName;
    }

    public function setAssignedToName(?string $assignedToName): self
    {
        $this->assignedToName = $assignedToName;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getFirstResponseAt(): ?\DateTimeImmutable
    {
        return $this->firstResponseAt;
    }

    public function setFirstResponseAt(?\DateTimeImmutable $firstResponseAt): self
    {
        $this->firstResponseAt = $firstResponseAt;
        return $this;
    }

    public function getResolvedAt(): ?\DateTimeImmutable
    {
        return $this->resolvedAt;
    }

    public function getClosedAt(): ?\DateTimeImmutable
    {
        return $this->closedAt;
    }

    public function getTags(): ?array
    {
        return $this->tags;
    }

    public function setTags(?array $tags): self
    {
        $this->tags = $tags;
        return $this;
    }

    public function addTag(string $tag): self
    {
        if (!in_array($tag, $this->tags ?? [])) {
            $tags = $this->tags ?? [];
            $tags[] = $tag;
            $this->tags = $tags;
        }
        return $this;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(SupportTicketMessage $message): self
    {
        if (!$this->messages->contains($message)) {
            $this->messages[] = $message;
            $message->setTicket($this);
        }

        // Set first response time if this is first agent response
        if ($message->isFromAgent() && !$this->firstResponseAt) {
            $this->firstResponseAt = new \DateTimeImmutable();
        }

        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    // Helper methods

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function isResolved(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    public function getResponseTimeMinutes(): ?int
    {
        if (!$this->firstResponseAt) {
            return null;
        }

        return (int) $this->createdAt->diff($this->firstResponseAt)->format('%i');
    }

    public function getResolutionTimeHours(): ?int
    {
        if (!$this->resolvedAt) {
            return null;
        }

        return (int) $this->createdAt->diff($this->resolvedAt)->format('%h');
    }

    public function getAgeDays(): int
    {
        $now = new \DateTimeImmutable();
        return (int) $this->createdAt->diff($now)->days;
    }
}
