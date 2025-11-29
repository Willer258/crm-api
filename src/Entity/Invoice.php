<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(name: 'invoice')]
#[ORM\Index(name: 'idx_invoice_number', columns: ['invoice_number'])]
#[ORM\Index(name: 'idx_status', columns: ['status'])]
class Invoice
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_OPEN = 'open';
    public const STATUS_PAID = 'paid';
    public const STATUS_VOID = 'void';
    public const STATUS_UNCOLLECTIBLE = 'uncollectible';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['invoice:list', 'invoice:info'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 50, unique: true)]
    #[Groups(['invoice:list', 'invoice:info'])]
    private string $invoiceNumber;

    #[ORM\ManyToOne(targetEntity: Subscription::class, inversedBy: 'invoices')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['invoice:info'])]
    private Subscription $subscription;

    #[ORM\Column(type: 'string', length: 20)]
    #[Groups(['invoice:list', 'invoice:info'])]
    private string $status;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Groups(['invoice:list', 'invoice:info'])]
    private float $subtotal;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Groups(['invoice:list', 'invoice:info'])]
    private float $tax = 0.00;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Groups(['invoice:list', 'invoice:info'])]
    private float $total;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2)]
    #[Groups(['invoice:info'])]
    private float $taxRate = 0.00;

    #[ORM\Column(type: 'string', length: 3)]
    #[Groups(['invoice:list', 'invoice:info'])]
    private string $currency = 'EUR';

    #[ORM\Column(type: 'json')]
    #[Groups(['invoice:info'])]
    private array $lineItems = [];

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['invoice:list', 'invoice:info'])]
    private \DateTimeImmutable $invoiceDate;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['invoice:list', 'invoice:info'])]
    private \DateTimeImmutable $dueDate;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['invoice:info'])]
    private ?\DateTimeImmutable $paidAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['invoice:info'])]
    private ?\DateTimeImmutable $voidedAt = null;

    // Customer information (denormalized for historical record)
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['invoice:info'])]
    private ?string $customerName = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['invoice:info'])]
    private ?string $customerEmail = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['invoice:info'])]
    private ?string $customerAddress = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    #[Groups(['invoice:info'])]
    private ?string $customerVatNumber = null;

    // Stripe integration
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $stripeInvoiceId = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $stripeHostedInvoiceUrl = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $stripeInvoicePdf = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['invoice:info'])]
    private ?string $notes = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['invoice:info'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['invoice:info'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'invoice', targetEntity: Payment::class)]
    private Collection $payments;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->invoiceDate = new \DateTimeImmutable();
        $this->dueDate = new \DateTimeImmutable('+30 days');
        $this->payments = new ArrayCollection();
    }

    // Getters and Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInvoiceNumber(): string
    {
        return $this->invoiceNumber;
    }

    public function setInvoiceNumber(string $invoiceNumber): self
    {
        $this->invoiceNumber = $invoiceNumber;
        return $this;
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

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getSubtotal(): float
    {
        return $this->subtotal;
    }

    public function setSubtotal(float $subtotal): self
    {
        $this->subtotal = $subtotal;
        return $this;
    }

    public function getTax(): float
    {
        return $this->tax;
    }

    public function setTax(float $tax): self
    {
        $this->tax = $tax;
        return $this;
    }

    public function getTotal(): float
    {
        return $this->total;
    }

    public function setTotal(float $total): self
    {
        $this->total = $total;
        return $this;
    }

    public function getTaxRate(): float
    {
        return $this->taxRate;
    }

    public function setTaxRate(float $taxRate): self
    {
        $this->taxRate = $taxRate;
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

    public function getLineItems(): array
    {
        return $this->lineItems;
    }

    public function setLineItems(array $lineItems): self
    {
        $this->lineItems = $lineItems;
        return $this;
    }

    public function getInvoiceDate(): \DateTimeImmutable
    {
        return $this->invoiceDate;
    }

    public function setInvoiceDate(\DateTimeImmutable $invoiceDate): self
    {
        $this->invoiceDate = $invoiceDate;
        return $this;
    }

    public function getDueDate(): \DateTimeImmutable
    {
        return $this->dueDate;
    }

    public function setDueDate(\DateTimeImmutable $dueDate): self
    {
        $this->dueDate = $dueDate;
        return $this;
    }

    public function getPaidAt(): ?\DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function setPaidAt(?\DateTimeImmutable $paidAt): self
    {
        $this->paidAt = $paidAt;
        return $this;
    }

    public function getVoidedAt(): ?\DateTimeImmutable
    {
        return $this->voidedAt;
    }

    public function setVoidedAt(?\DateTimeImmutable $voidedAt): self
    {
        $this->voidedAt = $voidedAt;
        return $this;
    }

    public function getCustomerName(): ?string
    {
        return $this->customerName;
    }

    public function setCustomerName(?string $customerName): self
    {
        $this->customerName = $customerName;
        return $this;
    }

    public function getCustomerEmail(): ?string
    {
        return $this->customerEmail;
    }

    public function setCustomerEmail(?string $customerEmail): self
    {
        $this->customerEmail = $customerEmail;
        return $this;
    }

    public function getCustomerAddress(): ?string
    {
        return $this->customerAddress;
    }

    public function setCustomerAddress(?string $customerAddress): self
    {
        $this->customerAddress = $customerAddress;
        return $this;
    }

    public function getCustomerVatNumber(): ?string
    {
        return $this->customerVatNumber;
    }

    public function setCustomerVatNumber(?string $customerVatNumber): self
    {
        $this->customerVatNumber = $customerVatNumber;
        return $this;
    }

    public function getStripeInvoiceId(): ?string
    {
        return $this->stripeInvoiceId;
    }

    public function setStripeInvoiceId(?string $stripeInvoiceId): self
    {
        $this->stripeInvoiceId = $stripeInvoiceId;
        return $this;
    }

    public function getStripeHostedInvoiceUrl(): ?string
    {
        return $this->stripeHostedInvoiceUrl;
    }

    public function setStripeHostedInvoiceUrl(?string $stripeHostedInvoiceUrl): self
    {
        $this->stripeHostedInvoiceUrl = $stripeHostedInvoiceUrl;
        return $this;
    }

    public function getStripeInvoicePdf(): ?string
    {
        return $this->stripeInvoicePdf;
    }

    public function setStripeInvoicePdf(?string $stripeInvoicePdf): self
    {
        $this->stripeInvoicePdf = $stripeInvoicePdf;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
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
     * @return Collection<int, Payment>
     */
    public function getPayments(): Collection
    {
        return $this->payments;
    }

    // Helper methods

    /**
     * Check if invoice is paid
     */
    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID && $this->paidAt !== null;
    }

    /**
     * Check if invoice is overdue
     */
    public function isOverdue(): bool
    {
        if ($this->isPaid() || $this->status === self::STATUS_VOID) {
            return false;
        }

        return $this->dueDate < new \DateTimeImmutable();
    }

    /**
     * Get days until due (negative if overdue)
     */
    public function getDaysUntilDue(): int
    {
        $now = new \DateTimeImmutable();
        $diff = $now->diff($this->dueDate);

        return $diff->invert ? -$diff->days : $diff->days;
    }

    /**
     * Calculate totals from line items
     */
    public function calculateTotals(): void
    {
        $subtotal = 0;

        foreach ($this->lineItems as $item) {
            $subtotal += ($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0);
        }

        $this->subtotal = $subtotal;
        $this->tax = round($subtotal * ($this->taxRate / 100), 2);
        $this->total = $this->subtotal + $this->tax;
    }

    /**
     * Get formatted total
     */
    public function getFormattedTotal(): string
    {
        $symbol = match($this->currency) {
            'EUR' => '€',
            'USD' => '$',
            'GBP' => '£',
            default => $this->currency
        };

        return number_format($this->total, 2) . ' ' . $symbol;
    }

    /**
     * Mark invoice as paid
     */
    public function markAsPaid(\DateTimeImmutable $paidAt = null): void
    {
        $this->status = self::STATUS_PAID;
        $this->paidAt = $paidAt ?? new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * Mark invoice as void
     */
    public function markAsVoid(): void
    {
        $this->status = self::STATUS_VOID;
        $this->voidedAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }
}
