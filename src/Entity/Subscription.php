<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(name: 'subscription')]
#[ORM\Index(name: 'idx_status', columns: ['status'])]
#[ORM\Index(name: 'idx_tenant', columns: ['tenant_id'])]
class Subscription
{
    public const STATUS_TRIALING = 'trialing';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAST_DUE = 'past_due';
    public const STATUS_CANCELED = 'canceled';
    public const STATUS_UNPAID = 'unpaid';
    public const STATUS_EXPIRED = 'expired';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['subscription:list', 'subscription:info'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Groups(['subscription:info'])]
    private ?string $tenantId = null;

    #[ORM\ManyToOne(targetEntity: Plan::class, inversedBy: 'subscriptions')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['subscription:list', 'subscription:info'])]
    private Plan $plan;

    #[ORM\Column(type: 'string', length: 20)]
    #[Groups(['subscription:list', 'subscription:info'])]
    private string $status;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['subscription:list', 'subscription:info'])]
    private \DateTimeImmutable $startDate;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['subscription:list', 'subscription:info'])]
    private ?\DateTimeImmutable $endDate = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['subscription:info'])]
    private ?\DateTimeImmutable $trialEndsAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['subscription:info'])]
    private ?\DateTimeImmutable $currentPeriodStart = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['subscription:info'])]
    private ?\DateTimeImmutable $currentPeriodEnd = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['subscription:info'])]
    private ?\DateTimeImmutable $canceledAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['subscription:info'])]
    private ?\DateTimeImmutable $cancelsAt = null;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['subscription:info'])]
    private bool $cancelAtPeriodEnd = false;

    // Stripe integration
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $stripeSubscriptionId = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $stripeCustomerId = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $stripePaymentMethodId = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['subscription:info'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['subscription:info'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'subscription', targetEntity: Invoice::class)]
    private Collection $invoices;

    #[ORM\OneToMany(mappedBy: 'subscription', targetEntity: UsageMetric::class)]
    private Collection $usageMetrics;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->startDate = new \DateTimeImmutable();
        $this->invoices = new ArrayCollection();
        $this->usageMetrics = new ArrayCollection();
    }

    // Getters and Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTenantId(): ?string
    {
        return $this->tenantId;
    }

    public function setTenantId(?string $tenantId): self
    {
        $this->tenantId = $tenantId;
        return $this;
    }

    public function getPlan(): Plan
    {
        return $this->plan;
    }

    public function setPlan(Plan $plan): self
    {
        $this->plan = $plan;
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

    public function getStartDate(): \DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeImmutable $startDate): self
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeImmutable $endDate): self
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function getTrialEndsAt(): ?\DateTimeImmutable
    {
        return $this->trialEndsAt;
    }

    public function setTrialEndsAt(?\DateTimeImmutable $trialEndsAt): self
    {
        $this->trialEndsAt = $trialEndsAt;
        return $this;
    }

    public function getCurrentPeriodStart(): ?\DateTimeImmutable
    {
        return $this->currentPeriodStart;
    }

    public function setCurrentPeriodStart(?\DateTimeImmutable $currentPeriodStart): self
    {
        $this->currentPeriodStart = $currentPeriodStart;
        return $this;
    }

    public function getCurrentPeriodEnd(): ?\DateTimeImmutable
    {
        return $this->currentPeriodEnd;
    }

    public function setCurrentPeriodEnd(?\DateTimeImmutable $currentPeriodEnd): self
    {
        $this->currentPeriodEnd = $currentPeriodEnd;
        return $this;
    }

    public function getCanceledAt(): ?\DateTimeImmutable
    {
        return $this->canceledAt;
    }

    public function setCanceledAt(?\DateTimeImmutable $canceledAt): self
    {
        $this->canceledAt = $canceledAt;
        return $this;
    }

    public function getCancelsAt(): ?\DateTimeImmutable
    {
        return $this->cancelsAt;
    }

    public function setCancelsAt(?\DateTimeImmutable $cancelsAt): self
    {
        $this->cancelsAt = $cancelsAt;
        return $this;
    }

    public function getCancelAtPeriodEnd(): bool
    {
        return $this->cancelAtPeriodEnd;
    }

    public function setCancelAtPeriodEnd(bool $cancelAtPeriodEnd): self
    {
        $this->cancelAtPeriodEnd = $cancelAtPeriodEnd;
        return $this;
    }

    public function getStripeSubscriptionId(): ?string
    {
        return $this->stripeSubscriptionId;
    }

    public function setStripeSubscriptionId(?string $stripeSubscriptionId): self
    {
        $this->stripeSubscriptionId = $stripeSubscriptionId;
        return $this;
    }

    public function getStripeCustomerId(): ?string
    {
        return $this->stripeCustomerId;
    }

    public function setStripeCustomerId(?string $stripeCustomerId): self
    {
        $this->stripeCustomerId = $stripeCustomerId;
        return $this;
    }

    public function getStripePaymentMethodId(): ?string
    {
        return $this->stripePaymentMethodId;
    }

    public function setStripePaymentMethodId(?string $stripePaymentMethodId): self
    {
        $this->stripePaymentMethodId = $stripePaymentMethodId;
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

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * @return Collection<int, Invoice>
     */
    public function getInvoices(): Collection
    {
        return $this->invoices;
    }

    /**
     * @return Collection<int, UsageMetric>
     */
    public function getUsageMetrics(): Collection
    {
        return $this->usageMetrics;
    }

    // Helper methods

    /**
     * Check if subscription is currently in trial
     */
    public function isInTrial(): bool
    {
        if ($this->trialEndsAt === null) {
            return false;
        }

        return $this->trialEndsAt > new \DateTimeImmutable() && $this->status === self::STATUS_TRIALING;
    }

    /**
     * Check if subscription is active (including trial)
     */
    public function isActive(): bool
    {
        return in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_TRIALING]);
    }

    /**
     * Check if subscription is canceled
     */
    public function isCanceled(): bool
    {
        return $this->status === self::STATUS_CANCELED;
    }

    /**
     * Check if subscription is past due
     */
    public function isPastDue(): bool
    {
        return $this->status === self::STATUS_PAST_DUE;
    }

    /**
     * Get days remaining in trial
     */
    public function getTrialDaysRemaining(): int
    {
        if ($this->trialEndsAt === null) {
            return 0;
        }

        $now = new \DateTimeImmutable();
        if ($this->trialEndsAt <= $now) {
            return 0;
        }

        return (int) $now->diff($this->trialEndsAt)->days;
    }

    /**
     * Get days until renewal
     */
    public function getDaysUntilRenewal(): int
    {
        if ($this->currentPeriodEnd === null) {
            return 0;
        }

        $now = new \DateTimeImmutable();
        if ($this->currentPeriodEnd <= $now) {
            return 0;
        }

        return (int) $now->diff($this->currentPeriodEnd)->days;
    }

    /**
     * Check if subscription will be canceled at period end
     */
    public function willBeCanceled(): bool
    {
        return $this->cancelAtPeriodEnd && $this->cancelsAt !== null;
    }
}
