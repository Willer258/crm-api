<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(name: 'usage_metric')]
#[ORM\Index(name: 'idx_subscription_date', columns: ['subscription_id', 'metric_date'])]
#[ORM\Index(name: 'idx_metric_type', columns: ['metric_type'])]
class UsageMetric
{
    public const METRIC_CONTACTS = 'contacts';
    public const METRIC_COMPANIES = 'companies';
    public const METRIC_DEALS = 'deals';
    public const METRIC_USERS = 'users';
    public const METRIC_STORAGE_BYTES = 'storage_bytes';
    public const METRIC_API_CALLS = 'api_calls';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['usage:list', 'usage:info'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Subscription::class, inversedBy: 'usageMetrics')]
    #[ORM\JoinColumn(nullable: false)]
    private Subscription $subscription;

    #[ORM\Column(type: 'string', length: 50)]
    #[Groups(['usage:list', 'usage:info'])]
    private string $metricType;

    #[ORM\Column(type: 'bigint')]
    #[Groups(['usage:list', 'usage:info'])]
    private int $value;

    #[ORM\Column(type: 'bigint', nullable: true)]
    #[Groups(['usage:info'])]
    private ?int $limit = null;

    #[ORM\Column(type: 'date')]
    #[Groups(['usage:list', 'usage:info'])]
    private \DateTimeInterface $metricDate;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->metricDate = new \DateTime();
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

    public function getMetricType(): string
    {
        return $this->metricType;
    }

    public function setMetricType(string $metricType): self
    {
        $this->metricType = $metricType;
        return $this;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function setValue(int $value): self
    {
        $this->value = $value;
        return $this;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function setLimit(?int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function getMetricDate(): \DateTimeInterface
    {
        return $this->metricDate;
    }

    public function setMetricDate(\DateTimeInterface $metricDate): self
    {
        $this->metricDate = $metricDate;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    // Helper methods

    /**
     * Get usage percentage
     */
    public function getUsagePercentage(): float
    {
        if ($this->limit === null || $this->limit === 0) {
            return 0.0;
        }

        return round(($this->value / $this->limit) * 100, 2);
    }

    /**
     * Check if quota is exceeded
     */
    public function isQuotaExceeded(): bool
    {
        if ($this->limit === null) {
            return false; // Unlimited
        }

        return $this->value >= $this->limit;
    }

    /**
     * Check if approaching limit (>= 80%)
     */
    public function isApproachingLimit(): bool
    {
        return $this->getUsagePercentage() >= 80.0;
    }

    /**
     * Get remaining quota
     */
    public function getRemainingQuota(): ?int
    {
        if ($this->limit === null) {
            return null; // Unlimited
        }

        $remaining = $this->limit - $this->value;
        return max(0, $remaining);
    }

    /**
     * Get formatted value based on metric type
     */
    public function getFormattedValue(): string
    {
        if ($this->metricType === self::METRIC_STORAGE_BYTES) {
            return $this->formatBytes($this->value);
        }

        return number_format($this->value);
    }

    /**
     * Get formatted limit based on metric type
     */
    public function getFormattedLimit(): string
    {
        if ($this->limit === null) {
            return 'Unlimited';
        }

        if ($this->metricType === self::METRIC_STORAGE_BYTES) {
            return $this->formatBytes($this->limit);
        }

        return number_format($this->limit);
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
