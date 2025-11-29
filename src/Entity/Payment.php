<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(name: 'payment')]
#[ORM\Index(name: 'idx_status', columns: ['status'])]
class Payment
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SUCCEEDED = 'succeeded';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELED = 'canceled';
    public const STATUS_REFUNDED = 'refunded';

    public const METHOD_CARD = 'card';
    public const METHOD_BANK_TRANSFER = 'bank_transfer';
    public const METHOD_SEPA_DEBIT = 'sepa_debit';
    public const METHOD_PAYPAL = 'paypal';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['payment:list', 'payment:info', 'invoice:info'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Invoice::class, inversedBy: 'payments')]
    #[ORM\JoinColumn(nullable: false)]
    private Invoice $invoice;

    #[ORM\Column(type: 'string', length: 20)]
    #[Groups(['payment:list', 'payment:info', 'invoice:info'])]
    private string $status;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Groups(['payment:list', 'payment:info', 'invoice:info'])]
    private float $amount;

    #[ORM\Column(type: 'string', length: 3)]
    #[Groups(['payment:list', 'payment:info', 'invoice:info'])]
    private string $currency = 'EUR';

    #[ORM\Column(type: 'string', length: 30)]
    #[Groups(['payment:list', 'payment:info'])]
    private string $paymentMethod;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $stripePaymentIntentId = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $stripeChargeId = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    #[Groups(['payment:info'])]
    private ?string $receiptUrl = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['payment:info'])]
    private ?string $failureReason = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['payment:info', 'invoice:info'])]
    private ?\DateTimeImmutable $paidAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['payment:info'])]
    private ?\DateTimeImmutable $failedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['payment:info'])]
    private ?\DateTimeImmutable $refundedAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['payment:info'])]
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

    public function getInvoice(): Invoice
    {
        return $this->invoice;
    }

    public function setInvoice(Invoice $invoice): self
    {
        $this->invoice = $invoice;
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

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): self
    {
        $this->amount = $amount;
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

    public function getPaymentMethod(): string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(string $paymentMethod): self
    {
        $this->paymentMethod = $paymentMethod;
        return $this;
    }

    public function getStripePaymentIntentId(): ?string
    {
        return $this->stripePaymentIntentId;
    }

    public function setStripePaymentIntentId(?string $stripePaymentIntentId): self
    {
        $this->stripePaymentIntentId = $stripePaymentIntentId;
        return $this;
    }

    public function getStripeChargeId(): ?string
    {
        return $this->stripeChargeId;
    }

    public function setStripeChargeId(?string $stripeChargeId): self
    {
        $this->stripeChargeId = $stripeChargeId;
        return $this;
    }

    public function getReceiptUrl(): ?string
    {
        return $this->receiptUrl;
    }

    public function setReceiptUrl(?string $receiptUrl): self
    {
        $this->receiptUrl = $receiptUrl;
        return $this;
    }

    public function getFailureReason(): ?string
    {
        return $this->failureReason;
    }

    public function setFailureReason(?string $failureReason): self
    {
        $this->failureReason = $failureReason;
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

    public function getFailedAt(): ?\DateTimeImmutable
    {
        return $this->failedAt;
    }

    public function setFailedAt(?\DateTimeImmutable $failedAt): self
    {
        $this->failedAt = $failedAt;
        return $this;
    }

    public function getRefundedAt(): ?\DateTimeImmutable
    {
        return $this->refundedAt;
    }

    public function setRefundedAt(?\DateTimeImmutable $refundedAt): self
    {
        $this->refundedAt = $refundedAt;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    // Helper methods

    public function isSuccessful(): bool
    {
        return $this->status === self::STATUS_SUCCEEDED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isRefunded(): bool
    {
        return $this->status === self::STATUS_REFUNDED;
    }
}
