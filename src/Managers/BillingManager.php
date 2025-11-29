<?php

namespace App\Managers;

use App\Entity\Invoice;
use App\Entity\Payment;
use App\Entity\Subscription;
use App\Repository\InvoiceRepository;
use App\Repository\PaymentRepository;
use App\Service\StripeService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class BillingManager extends Manager
{
    public function __construct(
        EntityManagerInterface $em,
        private InvoiceRepository $invoiceRepository,
        private PaymentRepository $paymentRepository,
        private StripeService $stripeService,
        private LoggerInterface $logger
    ) {
        parent::__construct($em, new \App\Utils\Sanitizer(), new \Symfony\Component\Serializer\Serializer([]));
    }

    /**
     * Create invoice for subscription
     */
    public function createInvoice(Subscription $subscription): Invoice
    {
        $plan = $subscription->getPlan();

        $invoice = new Invoice();
        $invoice->setSubscription($subscription);
        $invoice->setInvoiceNumber($this->invoiceRepository->getNextInvoiceNumber());
        $invoice->setStatus(Invoice::STATUS_DRAFT);
        $invoice->setCurrency($plan->getCurrency());

        // Set line items
        $lineItems = [
            [
                'description' => $plan->getName() . ' - ' . ucfirst($plan->getBillingInterval()),
                'quantity' => 1,
                'unit_price' => $plan->getPrice(),
                'total' => $plan->getPrice()
            ]
        ];

        $invoice->setLineItems($lineItems);
        $invoice->calculateTotals();

        $this->em->persist($invoice);
        $this->em->flush();

        $this->logger->info('Invoice created', [
            'invoice_id' => $invoice->getId(),
            'subscription_id' => $subscription->getId(),
            'amount' => $invoice->getTotal()
        ]);

        return $invoice;
    }

    /**
     * Mark invoice as paid
     */
    public function markInvoiceAsPaid(Invoice $invoice, string $stripeInvoiceId = null): Invoice
    {
        $invoice->markAsPaid();

        if ($stripeInvoiceId) {
            $invoice->setStripeInvoiceId($stripeInvoiceId);

            // Get Stripe invoice details
            try {
                $stripeInvoice = $this->stripeService->retrieveInvoice($stripeInvoiceId);
                $invoice->setStripeHostedInvoiceUrl($stripeInvoice['hosted_invoice_url']);
                $invoice->setStripeInvoicePdf($stripeInvoice['invoice_pdf']);
            } catch (\Exception $e) {
                $this->logger->warning('Failed to retrieve Stripe invoice details', [
                    'stripe_invoice_id' => $stripeInvoiceId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->em->flush();

        $this->logger->info('Invoice marked as paid', [
            'invoice_id' => $invoice->getId(),
            'stripe_invoice_id' => $stripeInvoiceId
        ]);

        return $invoice;
    }

    /**
     * Create payment record
     */
    public function createPayment(
        Invoice $invoice,
        float $amount,
        string $paymentMethod,
        string $status = Payment::STATUS_PENDING,
        ?string $stripePaymentIntentId = null
    ): Payment {
        $payment = new Payment();
        $payment->setInvoice($invoice);
        $payment->setAmount($amount);
        $payment->setCurrency($invoice->getCurrency());
        $payment->setPaymentMethod($paymentMethod);
        $payment->setStatus($status);
        $payment->setStripePaymentIntentId($stripePaymentIntentId);

        if ($status === Payment::STATUS_SUCCEEDED) {
            $payment->setPaidAt(new \DateTimeImmutable());
        }

        $this->em->persist($payment);
        $this->em->flush();

        $this->logger->info('Payment created', [
            'payment_id' => $payment->getId(),
            'invoice_id' => $invoice->getId(),
            'amount' => $amount,
            'status' => $status
        ]);

        return $payment;
    }

    /**
     * Update payment status
     */
    public function updatePaymentStatus(Payment $payment, string $status, ?string $failureReason = null): Payment
    {
        $payment->setStatus($status);

        switch ($status) {
            case Payment::STATUS_SUCCEEDED:
                $payment->setPaidAt(new \DateTimeImmutable());
                // Mark invoice as paid
                $this->markInvoiceAsPaid($payment->getInvoice());
                break;

            case Payment::STATUS_FAILED:
                $payment->setFailedAt(new \DateTimeImmutable());
                $payment->setFailureReason($failureReason);
                break;

            case Payment::STATUS_REFUNDED:
                $payment->setRefundedAt(new \DateTimeImmutable());
                break;
        }

        $this->em->flush();

        $this->logger->info('Payment status updated', [
            'payment_id' => $payment->getId(),
            'status' => $status
        ]);

        return $payment;
    }

    /**
     * Process subscription billing
     */
    public function processSubscriptionBilling(Subscription $subscription): Invoice
    {
        // Create invoice
        $invoice = $this->createInvoice($subscription);

        // If subscription has Stripe ID, let Stripe handle it
        if ($subscription->getStripeSubscriptionId()) {
            $invoice->setStatus(Invoice::STATUS_OPEN);
            $this->em->flush();

            $this->logger->info('Subscription billing delegated to Stripe', [
                'subscription_id' => $subscription->getId(),
                'stripe_subscription_id' => $subscription->getStripeSubscriptionId()
            ]);

            return $invoice;
        }

        // Manual billing logic here
        // ...

        return $invoice;
    }

    /**
     * Retry failed payment
     */
    public function retryFailedPayment(Payment $payment): Payment
    {
        if (!$payment->isFailed()) {
            throw new \RuntimeException('Payment is not in failed status');
        }

        $payment->setStatus(Payment::STATUS_PENDING);
        $payment->setFailedAt(null);
        $payment->setFailureReason(null);

        $this->em->flush();

        $this->logger->info('Payment retry initiated', [
            'payment_id' => $payment->getId()
        ]);

        // Trigger Stripe retry if has Stripe payment intent
        if ($payment->getStripePaymentIntentId()) {
            // Stripe will handle retry via webhooks
        }

        return $payment;
    }

    /**
     * Get invoices for subscription
     */
    public function getInvoicesForSubscription(Subscription $subscription): array
    {
        return $this->invoiceRepository->findBySubscription($subscription);
    }

    /**
     * Get payments for invoice
     */
    public function getPaymentsForInvoice(Invoice $invoice): array
    {
        return $this->paymentRepository->findByInvoice($invoice);
    }

    /**
     * Calculate total revenue
     */
    public function calculateTotalRevenue(?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null): float
    {
        return $this->invoiceRepository->calculateTotalRevenue($startDate, $endDate);
    }

    /**
     * Get payment success rate
     */
    public function getPaymentSuccessRate(?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null): float
    {
        return $this->paymentRepository->calculateSuccessRate($startDate, $endDate);
    }

    /**
     * Get overdue invoices
     */
    public function getOverdueInvoices(): array
    {
        return $this->invoiceRepository->findOverdue();
    }

    /**
     * Get unpaid invoices
     */
    public function getUnpaidInvoices(): array
    {
        return $this->invoiceRepository->findUnpaid();
    }

    /**
     * Get failed payments
     */
    public function getFailedPayments(): array
    {
        return $this->paymentRepository->findFailed();
    }
}
