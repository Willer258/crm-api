<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(name: 'plan')]
class Plan
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['plan:list', 'plan:info', 'subscription:info'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100, unique: true)]
    #[Groups(['plan:list', 'plan:info', 'subscription:info'])]
    private string $code;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['plan:list', 'plan:info', 'subscription:info'])]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['plan:list', 'plan:info'])]
    private ?string $description = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Groups(['plan:list', 'plan:info', 'subscription:info'])]
    private float $price;

    #[ORM\Column(type: 'string', length: 3)]
    #[Groups(['plan:list', 'plan:info', 'subscription:info'])]
    private string $currency = 'EUR';

    #[ORM\Column(type: 'string', length: 20)]
    #[Groups(['plan:list', 'plan:info', 'subscription:info'])]
    private string $billingInterval; // monthly, yearly

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['plan:info'])]
    private ?int $trialDays = 14;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['plan:list', 'plan:info'])]
    private bool $isActive = true;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['plan:list', 'plan:info'])]
    private bool $isPublic = true;

    #[ORM\Column(type: 'integer')]
    #[Groups(['plan:info'])]
    private int $sortOrder = 0;

    // Quotas
    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['plan:info', 'subscription:info'])]
    private ?int $maxContacts = null; // null = unlimited

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['plan:info', 'subscription:info'])]
    private ?int $maxCompanies = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['plan:info', 'subscription:info'])]
    private ?int $maxDeals = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['plan:info', 'subscription:info'])]
    private ?int $maxUsers = null;

    #[ORM\Column(type: 'bigint', nullable: true)]
    #[Groups(['plan:info', 'subscription:info'])]
    private ?int $maxStorageBytes = null; // in bytes

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['plan:info', 'subscription:info'])]
    private ?int $maxApiCallsPerDay = null;

    // Features
    #[ORM\Column(type: 'json')]
    #[Groups(['plan:info', 'subscription:info'])]
    private array $features = [];

    // Stripe integration
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $stripePriceId = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $stripeProductId = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['plan:info'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['plan:info'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'plan', targetEntity: Subscription::class)]
    private Collection $subscriptions;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->subscriptions = new ArrayCollection();
    }

    // Getters and Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
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

    public function getPrice(): float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;
        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;
        return $this;
    }

    public function getBillingInterval(): string
    {
        return $this->billingInterval;
    }

    public function setBillingInterval(string $billingInterval): self
    {
        $this->billingInterval = $billingInterval;
        return $this;
    }

    public function getTrialDays(): ?int
    {
        return $this->trialDays;
    }

    public function setTrialDays(?int $trialDays): self
    {
        $this->trialDays = $trialDays;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function isPublic(): bool
    {
        return $this->isPublic;
    }

    public function setIsPublic(bool $isPublic): self
    {
        $this->isPublic = $isPublic;
        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): self
    {
        $this->sortOrder = $sortOrder;
        return $this;
    }

    public function getMaxContacts(): ?int
    {
        return $this->maxContacts;
    }

    public function setMaxContacts(?int $maxContacts): self
    {
        $this->maxContacts = $maxContacts;
        return $this;
    }

    public function getMaxCompanies(): ?int
    {
        return $this->maxCompanies;
    }

    public function setMaxCompanies(?int $maxCompanies): self
    {
        $this->maxCompanies = $maxCompanies;
        return $this;
    }

    public function getMaxDeals(): ?int
    {
        return $this->maxDeals;
    }

    public function setMaxDeals(?int $maxDeals): self
    {
        $this->maxDeals = $maxDeals;
        return $this;
    }

    public function getMaxUsers(): ?int
    {
        return $this->maxUsers;
    }

    public function setMaxUsers(?int $maxUsers): self
    {
        $this->maxUsers = $maxUsers;
        return $this;
    }

    public function getMaxStorageBytes(): ?int
    {
        return $this->maxStorageBytes;
    }

    public function setMaxStorageBytes(?int $maxStorageBytes): self
    {
        $this->maxStorageBytes = $maxStorageBytes;
        return $this;
    }

    public function getMaxApiCallsPerDay(): ?int
    {
        return $this->maxApiCallsPerDay;
    }

    public function setMaxApiCallsPerDay(?int $maxApiCallsPerDay): self
    {
        $this->maxApiCallsPerDay = $maxApiCallsPerDay;
        return $this;
    }

    public function getFeatures(): array
    {
        return $this->features;
    }

    public function setFeatures(array $features): self
    {
        $this->features = $features;
        return $this;
    }

    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features);
    }

    public function getStripePriceId(): ?string
    {
        return $this->stripePriceId;
    }

    public function setStripePriceId(?string $stripePriceId): self
    {
        $this->stripePriceId = $stripePriceId;
        return $this;
    }

    public function getStripeProductId(): ?string
    {
        return $this->stripeProductId;
    }

    public function setStripeProductId(?string $stripeProductId): self
    {
        $this->stripeProductId = $stripeProductId;
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
     * @return Collection<int, Subscription>
     */
    public function getSubscriptions(): Collection
    {
        return $this->subscriptions;
    }

    /**
     * Helper to get formatted price
     */
    public function getFormattedPrice(): string
    {
        $symbol = match($this->currency) {
            'EUR' => '€',
            'USD' => '$',
            'GBP' => '£',
            default => $this->currency
        };

        return number_format($this->price, 2) . ' ' . $symbol;
    }

    /**
     * Helper to get storage in human-readable format
     */
    public function getFormattedMaxStorage(): string
    {
        if ($this->maxStorageBytes === null) {
            return 'Unlimited';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = $this->maxStorageBytes;
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Check if plan has unlimited quota for a resource
     */
    public function hasUnlimitedQuota(string $resource): bool
    {
        return match($resource) {
            'contacts' => $this->maxContacts === null,
            'companies' => $this->maxCompanies === null,
            'deals' => $this->maxDeals === null,
            'users' => $this->maxUsers === null,
            'storage' => $this->maxStorageBytes === null,
            'api_calls' => $this->maxApiCallsPerDay === null,
            default => false
        };
    }
}
