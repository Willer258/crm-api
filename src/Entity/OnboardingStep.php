<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(name: 'onboarding_step')]
#[ORM\Index(name: 'idx_tenant', columns: ['tenant_id'])]
#[ORM\Index(name: 'idx_status', columns: ['status'])]
class OnboardingStep
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_SKIPPED = 'skipped';

    // Step types
    public const STEP_EMAIL_VERIFICATION = 'email_verification';
    public const STEP_COMPANY_PROFILE = 'company_profile';
    public const STEP_INITIAL_CONFIG = 'initial_config';
    public const STEP_DATA_IMPORT = 'data_import';
    public const STEP_INVITE_USERS = 'invite_users';
    public const STEP_TOUR_COMPLETED = 'tour_completed';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['onboarding:read'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100)]
    #[Groups(['onboarding:read'])]
    private string $tenantId;

    #[ORM\Column(type: 'string', length: 50)]
    #[Groups(['onboarding:read'])]
    private string $stepType;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['onboarding:read'])]
    private string $stepName;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['onboarding:read'])]
    private ?string $description = null;

    #[ORM\Column(type: 'integer')]
    #[Groups(['onboarding:read'])]
    private int $orderIndex = 0;

    #[ORM\Column(type: 'string', length: 20)]
    #[Groups(['onboarding:read'])]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['onboarding:read'])]
    private bool $isRequired = true;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['onboarding:read'])]
    private ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['onboarding:read'])]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['onboarding:read'])]
    private ?array $metadata = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['onboarding:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // Getters and Setters

    public function getId(): ?int
    {
        return $this->id;
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

    public function getStepType(): string
    {
        return $this->stepType;
    }

    public function setStepType(string $stepType): self
    {
        $this->stepType = $stepType;
        return $this;
    }

    public function getStepName(): string
    {
        return $this->stepName;
    }

    public function setStepName(string $stepName): self
    {
        $this->stepName = $stepName;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getOrderIndex(): int
    {
        return $this->orderIndex;
    }

    public function setOrderIndex(int $orderIndex): self
    {
        $this->orderIndex = $orderIndex;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function isRequired(): bool
    {
        return $this->isRequired;
    }

    public function setIsRequired(bool $isRequired): self
    {
        $this->isRequired = $isRequired;
        return $this;
    }

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(?\DateTimeImmutable $startedAt): self
    {
        $this->startedAt = $startedAt;
        return $this;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeImmutable $completedAt): self
    {
        $this->completedAt = $completedAt;
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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    // Helper methods

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isSkipped(): bool
    {
        return $this->status === self::STATUS_SKIPPED;
    }

    public function markAsStarted(): self
    {
        $this->status = self::STATUS_IN_PROGRESS;
        $this->startedAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function markAsCompleted(?array $metadata = null): self
    {
        $this->status = self::STATUS_COMPLETED;
        $this->completedAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();

        if ($metadata) {
            $this->metadata = array_merge($this->metadata ?? [], $metadata);
        }

        return $this;
    }

    public function markAsSkipped(): self
    {
        $this->status = self::STATUS_SKIPPED;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getDuration(): ?int
    {
        if (!$this->startedAt || !$this->completedAt) {
            return null;
        }

        return $this->completedAt->getTimestamp() - $this->startedAt->getTimestamp();
    }
}
