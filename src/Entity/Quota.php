<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(name: 'quota')]
#[ORM\UniqueConstraint(name: 'unique_tenant_quota', columns: ['tenant_id', 'quota_type'])]
class Quota
{
    public const TYPE_CONTACTS = 'contacts';
    public const TYPE_COMPANIES = 'companies';
    public const TYPE_DEALS = 'deals';
    public const TYPE_USERS = 'users';
    public const TYPE_STORAGE = 'storage';
    public const TYPE_API_CALLS = 'api_calls';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['quota:list', 'quota:info'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100)]
    #[Groups(['quota:list', 'quota:info'])]
    private string $tenantId;

    #[ORM\Column(type: 'string', length: 50)]
    #[Groups(['quota:list', 'quota:info'])]
    private string $quotaType;

    #[ORM\Column(type: 'bigint', nullable: true)]
    #[Groups(['quota:list', 'quota:info'])]
    private ?int $limitValue = null; // null = unlimited

    #[ORM\Column(type: 'bigint')]
    #[Groups(['quota:list', 'quota:info'])]
    private int $currentUsage = 0;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['quota:info'])]
    private bool $isHardLimit = true; // true = block when exceeded, false = allow with warning

    #[ORM\Column(type: 'boolean')]
    #[Groups(['quota:info'])]
    private bool $notifyAtThreshold = true;

    #[ORM\Column(type: 'integer')]
    #[Groups(['quota:info'])]
    private int $notificationThreshold = 80; // Notify at 80%

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['quota:info'])]
    private ?\DateTimeImmutable $lastNotifiedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['quota:info'])]
    private ?\DateTimeImmutable $lastResetAt = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    #[Groups(['quota:info'])]
    private ?string $resetInterval = null; // 'daily', 'monthly', null for no reset

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['quota:info'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['quota:info'])]
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

    public function getQuotaType(): string
    {
        return $this->quotaType;
    }

    public function setQuotaType(string $quotaType): self
    {
        $this->quotaType = $quotaType;
        return $this;
    }

    public function getLimitValue(): ?int
    {
        return $this->limitValue;
    }

    public function setLimitValue(?int $limitValue): self
    {
        $this->limitValue = $limitValue;
        return $this;
    }

    public function getCurrentUsage(): int
    {
        return $this->currentUsage;
    }

    public function setCurrentUsage(int $currentUsage): self
    {
        $this->currentUsage = $currentUsage;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function isHardLimit(): bool
    {
        return $this->isHardLimit;
    }

    public function setIsHardLimit(bool $isHardLimit): self
    {
        $this->isHardLimit = $isHardLimit;
        return $this;
    }

    public function getNotifyAtThreshold(): bool
    {
        return $this->notifyAtThreshold;
    }

    public function setNotifyAtThreshold(bool $notifyAtThreshold): self
    {
        $this->notifyAtThreshold = $notifyAtThreshold;
        return $this;
    }

    public function getNotificationThreshold(): int
    {
        return $this->notificationThreshold;
    }

    public function setNotificationThreshold(int $notificationThreshold): self
    {
        $this->notificationThreshold = $notificationThreshold;
        return $this;
    }

    public function getLastNotifiedAt(): ?\DateTimeImmutable
    {
        return $this->lastNotifiedAt;
    }

    public function setLastNotifiedAt(?\DateTimeImmutable $lastNotifiedAt): self
    {
        $this->lastNotifiedAt = $lastNotifiedAt;
        return $this;
    }

    public function getLastResetAt(): ?\DateTimeImmutable
    {
        return $this->lastResetAt;
    }

    public function setLastResetAt(?\DateTimeImmutable $lastResetAt): self
    {
        $this->lastResetAt = $lastResetAt;
        return $this;
    }

    public function getResetInterval(): ?string
    {
        return $this->resetInterval;
    }

    public function setResetInterval(?string $resetInterval): self
    {
        $this->resetInterval = $resetInterval;
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

    /**
     * Check if quota is unlimited
     */
    public function isUnlimited(): bool
    {
        return $this->limitValue === null;
    }

    /**
     * Check if quota is exceeded
     */
    public function isExceeded(): bool
    {
        if ($this->isUnlimited()) {
            return false;
        }

        return $this->currentUsage >= $this->limitValue;
    }

    /**
     * Get usage percentage
     */
    public function getUsagePercentage(): float
    {
        if ($this->isUnlimited() || $this->limitValue === 0) {
            return 0.0;
        }

        return round(($this->currentUsage / $this->limitValue) * 100, 2);
    }

    /**
     * Check if approaching threshold
     */
    public function isApproachingThreshold(): bool
    {
        return $this->getUsagePercentage() >= $this->notificationThreshold;
    }

    /**
     * Get remaining quota
     */
    public function getRemainingQuota(): ?int
    {
        if ($this->isUnlimited()) {
            return null;
        }

        $remaining = $this->limitValue - $this->currentUsage;
        return max(0, $remaining);
    }

    /**
     * Increment usage
     */
    public function incrementUsage(int $amount = 1): self
    {
        $this->currentUsage += $amount;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    /**
     * Decrement usage
     */
    public function decrementUsage(int $amount = 1): self
    {
        $this->currentUsage = max(0, $this->currentUsage - $amount);
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    /**
     * Reset usage to zero
     */
    public function resetUsage(): self
    {
        $this->currentUsage = 0;
        $this->lastResetAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    /**
     * Check if can perform action
     */
    public function canPerformAction(int $requiredAmount = 1): bool
    {
        if ($this->isUnlimited()) {
            return true;
        }

        if (!$this->isHardLimit) {
            return true; // Soft limit, allow but warn
        }

        return ($this->currentUsage + $requiredAmount) <= $this->limitValue;
    }

    /**
     * Should send notification
     */
    public function shouldNotify(): bool
    {
        if (!$this->notifyAtThreshold) {
            return false;
        }

        if (!$this->isApproachingThreshold()) {
            return false;
        }

        // Don't notify if already notified in the last 24 hours
        if ($this->lastNotifiedAt !== null) {
            $dayAgo = new \DateTimeImmutable('-24 hours');
            if ($this->lastNotifiedAt > $dayAgo) {
                return false;
            }
        }

        return true;
    }

    /**
     * Mark as notified
     */
    public function markAsNotified(): self
    {
        $this->lastNotifiedAt = new \DateTimeImmutable();
        return $this;
    }

    /**
     * Get formatted usage based on quota type
     */
    public function getFormattedUsage(): string
    {
        if ($this->quotaType === self::TYPE_STORAGE) {
            return $this->formatBytes($this->currentUsage);
        }

        return number_format($this->currentUsage);
    }

    /**
     * Get formatted limit based on quota type
     */
    public function getFormattedLimit(): string
    {
        if ($this->isUnlimited()) {
            return 'Unlimited';
        }

        if ($this->quotaType === self::TYPE_STORAGE) {
            return $this->formatBytes($this->limitValue);
        }

        return number_format($this->limitValue);
    }

    /**
     * Format bytes to human-readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        $value = $bytes;

        while ($value >= 1024 && $i < count($units) - 1) {
            $value /= 1024;
            $i++;
        }

        return round($value, 2) . ' ' . $units[$i];
    }
}
