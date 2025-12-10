<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(name: 'dunning_attempt')]
#[ORM\Index(name: 'idx_subscription', columns: ['subscription_id'])]
#[ORM\Index(name: 'idx_status', columns: ['status'])]
class DunningAttempt
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    public const STATUS_RESOLVED = 'resolved';

    public const ACTION_EMAIL_NOTIFICATION = 'email_notification';
    public const ACTION_EMAIL_REMINDER = 'email_reminder';
    public const ACTION_RESTRICT_ACCESS = 'restrict_access';
    public const ACTION_SUSPEND_ACCOUNT = 'suspend_account';
    public const ACTION_SCHEDULE_DELETION = 'schedule_deletion';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['dunning:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Subscription::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['dunning:read'])]
    private Subscription $subscription;

    #[ORM\Column(type: 'integer')]
    #[Groups(['dunning:read'])]
    private int $attemptNumber = 1;

    #[ORM\Column(type: 'string', length: 50)]
    #[Groups(['dunning:read'])]
    private string $action;

    #[ORM\Column(type: 'string', length: 20)]
    #[Groups(['dunning:read'])]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['dunning:read'])]
    private \DateTimeImmutable $scheduledAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['dunning:read'])]
    private ?\DateTimeImmutable $executedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['dunning:read'])]
    private ?\DateTimeImmutable $resolvedAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['dunning:read'])]
    private ?string $message = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['dunning:read'])]
    private ?array $metadata = null;

    #[ORM\Column(type: 'datetime_immutable')]
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

    public function getSubscription(): Subscription
    {
        return $this->subscription;
    }

    public function setSubscription(Subscription $subscription): self
    {
        $this->subscription = $subscription;
        return $this;
    }

    public function getAttemptNumber(): int
    {
        return $this->attemptNumber;
    }

    public function setAttemptNumber(int $attemptNumber): self
    {
        $this->attemptNumber = $attemptNumber;
        return $this;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action): self
    {
        $this->action = $action;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getScheduledAt(): \DateTimeImmutable
    {
        return $this->scheduledAt;
    }

    public function setScheduledAt(\DateTimeImmutable $scheduledAt): self
    {
        $this->scheduledAt = $scheduledAt;
        return $this;
    }

    public function getExecutedAt(): ?\DateTimeImmutable
    {
        return $this->executedAt;
    }

    public function setExecutedAt(?\DateTimeImmutable $executedAt): self
    {
        $this->executedAt = $executedAt;
        return $this;
    }

    public function getResolvedAt(): ?\DateTimeImmutable
    {
        return $this->resolvedAt;
    }

    public function setResolvedAt(?\DateTimeImmutable $resolvedAt): self
    {
        $this->resolvedAt = $resolvedAt;
        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): self
    {
        $this->message = $message;
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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    // Helper methods

    public function markAsExecuted(?string $message = null): self
    {
        $this->status = self::STATUS_SENT;
        $this->executedAt = new \DateTimeImmutable();
        if ($message) {
            $this->message = $message;
        }
        return $this;
    }

    public function markAsFailed(string $message): self
    {
        $this->status = self::STATUS_FAILED;
        $this->message = $message;
        return $this;
    }

    public function markAsResolved(): self
    {
        $this->status = self::STATUS_RESOLVED;
        $this->resolvedAt = new \DateTimeImmutable();
        return $this;
    }

    public function isDue(): bool
    {
        return $this->scheduledAt <= new \DateTimeImmutable()
            && $this->status === self::STATUS_PENDING;
    }
}
