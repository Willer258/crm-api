<?php

namespace App\Service;

use App\Entity\Plan;
use App\Entity\Subscription;
use App\Entity\Payment;
use App\Entity\Invoice;
use App\Repository\SubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Sandbox/Test mode service for development
 * Allows creating subscriptions without real Stripe payments
 */
class SandboxModeService
{
    private bool $sandboxMode;

    public function __construct(
        private EntityManagerInterface $em,
        private SubscriptionRepository $subscriptionRepository,
        private LoggerInterface $logger,
        private string $appEnv
    ) {
        // Enable sandbox mode in dev/test environments
        $this->sandboxMode = in_array($appEnv, ['dev', 'test']);
    }

    /**
     * Check if sandbox mode is enabled
     */
    public function isSandboxMode(): bool
    {
        return $this->sandboxMode;
    }

    /**
     * Enable/disable sandbox mode (for testing)
     */
    public function setSandboxMode(bool $enabled): void
    {
        $this->sandboxMode = $enabled;
    }

    /**
     * Create a sandbox subscription without Stripe
     */
    public function createSandboxSubscription(
        string $tenantId,
        Plan $plan,
        string $userEmail,
        ?int $trialDays = null
    ): Subscription {
        if (!$this->sandboxMode) {
            throw new \RuntimeException('Sandbox mode is not enabled');
        }

        $this->logger->info('Creating sandbox subscription', [
            'tenant_id' => $tenantId,
            'plan' => $plan->getCode(),
            'email' => $userEmail
        ]);

        $subscription = new Subscription();
        $subscription->setTenantId($tenantId);
        $subscription->setPlan($plan);

        // Set trial or active status
        $trialDays = $trialDays ?? $plan->getTrialDays();
        if ($trialDays && $trialDays > 0) {
            $subscription->setStatus(Subscription::STATUS_TRIALING);
            $subscription->setTrialEndsAt(
                new \DateTimeImmutable("+{$trialDays} days")
            );
        } else {
            $subscription->setStatus(Subscription::STATUS_ACTIVE);
        }

        // Set billing period
        $now = new \DateTimeImmutable();
        $subscription->setCurrentPeriodStart($now);

        $periodEnd = match($plan->getBillingInterval()) {
            'monthly' => $now->modify('+1 month'),
            'yearly' => $now->modify('+1 year'),
            default => $now->modify('+1 month')
        };
        $subscription->setCurrentPeriodEnd($periodEnd);

        // Set fake Stripe IDs for testing
        $subscription->setStripeSubscriptionId('sub_sandbox_' . uniqid());
        $subscription->setStripeCustomerId('cus_sandbox_' . uniqid());

        $this->em->persist($subscription);
        $this->em->flush();

        // Create a fake successful invoice
        $this->createSandboxInvoice($subscription);

        $this->logger->info('Sandbox subscription created', [
            'subscription_id' => $subscription->getId(),
            'tenant_id' => $tenantId
        ]);

        return $subscription;
    }

    /**
     * Create a sandbox invoice (fake)
     */
    private function createSandboxInvoice(Subscription $subscription): Invoice
    {
        $plan = $subscription->getPlan();

        $invoice = new Invoice();
        $invoice->setSubscription($subscription);
        $invoice->setInvoiceNumber('INV-SANDBOX-' . date('Ymd') . '-' . uniqid());
        $invoice->setInvoiceDate(new \DateTimeImmutable());
        $invoice->setDueDate(new \DateTimeImmutable('+7 days'));
        $invoice->setStatus(Invoice::STATUS_PAID);
        $invoice->setSubtotal($plan->getPrice());
        $invoice->setTax(0);
        $invoice->setTotal($plan->getPrice());
        $invoice->setCurrency($plan->getCurrency());
        $invoice->setPaidAt(new \DateTimeImmutable());
        $invoice->setStripeInvoiceId('in_sandbox_' . uniqid());

        // Set line items
        $invoice->setLineItems([
            [
                'description' => $plan->getName() . ' - ' . ucfirst($plan->getBillingInterval()) . ' subscription',
                'quantity' => 1,
                'unit_price' => $plan->getPrice(),
                'total' => $plan->getPrice()
            ]
        ]);

        $this->em->persist($invoice);
        $this->em->flush();

        // Create corresponding payment
        $this->createSandboxPayment($invoice);

        return $invoice;
    }

    /**
     * Create a sandbox payment (fake)
     */
    private function createSandboxPayment(Invoice $invoice): Payment
    {
        $payment = new Payment();
        $payment->setInvoice($invoice);
        $payment->setSubscription($invoice->getSubscription());
        $payment->setAmount($invoice->getTotal());
        $payment->setCurrency($invoice->getCurrency());
        $payment->setStatus(Payment::STATUS_SUCCEEDED);
        $payment->setPaymentMethod('sandbox_card');
        $payment->setStripePaymentIntentId('pi_sandbox_' . uniqid());
        $payment->setTransactionDate(new \DateTimeImmutable());
        $payment->setMetadata([
            'sandbox' => true,
            'card_brand' => 'visa',
            'card_last4' => '4242'
        ]);

        $this->em->persist($payment);
        $this->em->flush();

        return $payment;
    }

    /**
     * Simulate subscription renewal (for testing)
     */
    public function simulateRenewal(Subscription $subscription): Subscription
    {
        if (!$this->sandboxMode) {
            throw new \RuntimeException('Sandbox mode is not enabled');
        }

        $this->logger->info('Simulating subscription renewal', [
            'subscription_id' => $subscription->getId()
        ]);

        $plan = $subscription->getPlan();
        $currentEnd = $subscription->getCurrentPeriodEnd();

        $newPeriodEnd = match($plan->getBillingInterval()) {
            'monthly' => $currentEnd->modify('+1 month'),
            'yearly' => $currentEnd->modify('+1 year'),
            default => $currentEnd->modify('+1 month')
        };

        $subscription->setCurrentPeriodStart($currentEnd);
        $subscription->setCurrentPeriodEnd($newPeriodEnd);
        $subscription->setStatus(Subscription::STATUS_ACTIVE);
        $subscription->setUpdatedAt(new \DateTimeImmutable());

        $this->em->flush();

        // Create new invoice
        $this->createSandboxInvoice($subscription);

        $this->logger->info('Subscription renewal simulated', [
            'subscription_id' => $subscription->getId(),
            'new_period_end' => $newPeriodEnd->format('Y-m-d')
        ]);

        return $subscription;
    }

    /**
     * Simulate payment failure (for testing dunning)
     */
    public function simulatePaymentFailure(Subscription $subscription): Subscription
    {
        if (!$this->sandboxMode) {
            throw new \RuntimeException('Sandbox mode is not enabled');
        }

        $this->logger->info('Simulating payment failure', [
            'subscription_id' => $subscription->getId()
        ]);

        $subscription->setStatus(Subscription::STATUS_PAST_DUE);
        $subscription->setUpdatedAt(new \DateTimeImmutable());

        $this->em->flush();

        // Create failed invoice
        $plan = $subscription->getPlan();
        $invoice = new Invoice();
        $invoice->setSubscription($subscription);
        $invoice->setInvoiceNumber('INV-SANDBOX-FAILED-' . date('Ymd') . '-' . uniqid());
        $invoice->setInvoiceDate(new \DateTimeImmutable());
        $invoice->setDueDate(new \DateTimeImmutable('+7 days'));
        $invoice->setStatus(Invoice::STATUS_FAILED);
        $invoice->setSubtotal($plan->getPrice());
        $invoice->setTax(0);
        $invoice->setTotal($plan->getPrice());
        $invoice->setCurrency($plan->getCurrency());
        $invoice->setStripeInvoiceId('in_sandbox_failed_' . uniqid());

        $this->em->persist($invoice);
        $this->em->flush();

        return $subscription;
    }

    /**
     * Simulate subscription cancellation
     */
    public function simulateCancellation(Subscription $subscription, bool $immediately = false): Subscription
    {
        if (!$this->sandboxMode) {
            throw new \RuntimeException('Sandbox mode is not enabled');
        }

        $this->logger->info('Simulating subscription cancellation', [
            'subscription_id' => $subscription->getId(),
            'immediately' => $immediately
        ]);

        if ($immediately) {
            $subscription->setStatus(Subscription::STATUS_CANCELED);
            $subscription->setCanceledAt(new \DateTimeImmutable());
            $subscription->setEndDate(new \DateTimeImmutable());
        } else {
            $subscription->setCancelAtPeriodEnd(true);
            $subscription->setCancelsAt($subscription->getCurrentPeriodEnd());
        }

        $subscription->setUpdatedAt(new \DateTimeImmutable());
        $this->em->flush();

        return $subscription;
    }

    /**
     * Get sandbox test cards (like Stripe test cards)
     */
    public function getTestCards(): array
    {
        return [
            [
                'number' => '4242424242424242',
                'brand' => 'Visa',
                'description' => 'Success - No authentication required'
            ],
            [
                'number' => '4000002500003155',
                'brand' => 'Visa',
                'description' => 'Requires 3D Secure authentication'
            ],
            [
                'number' => '4000000000009995',
                'brand' => 'Visa',
                'description' => 'Declined - Insufficient funds'
            ],
            [
                'number' => '4000000000000002',
                'brand' => 'Visa',
                'description' => 'Declined - Generic decline'
            ],
        ];
    }

    /**
     * Create multiple test subscriptions for development
     */
    public function seedTestSubscriptions(array $plans, int $count = 10): array
    {
        if (!$this->sandboxMode) {
            throw new \RuntimeException('Sandbox mode is not enabled');
        }

        $this->logger->info('Seeding test subscriptions', ['count' => $count]);

        $subscriptions = [];
        $statuses = [
            Subscription::STATUS_ACTIVE,
            Subscription::STATUS_TRIALING,
            Subscription::STATUS_PAST_DUE,
            Subscription::STATUS_CANCELED
        ];

        for ($i = 1; $i <= $count; $i++) {
            $tenantId = 'test-tenant-' . str_pad($i, 3, '0', STR_PAD_LEFT);
            $plan = $plans[array_rand($plans)];
            $status = $statuses[array_rand($statuses)];

            $subscription = $this->createSandboxSubscription(
                $tenantId,
                $plan,
                "test{$i}@example.com"
            );

            // Override status for variety
            $subscription->setStatus($status);
            $this->em->flush();

            $subscriptions[] = $subscription;
        }

        $this->logger->info('Test subscriptions seeded', ['created' => count($subscriptions)]);

        return $subscriptions;
    }

    /**
     * Clean up all sandbox subscriptions
     */
    public function cleanupSandboxData(): int
    {
        if (!$this->sandboxMode) {
            throw new \RuntimeException('Sandbox mode is not enabled');
        }

        $this->logger->info('Cleaning up sandbox data');

        // Find all subscriptions with sandbox Stripe IDs
        $qb = $this->em->createQueryBuilder();
        $qb->delete(Subscription::class, 's')
           ->where('s.stripeSubscriptionId LIKE :sandbox')
           ->setParameter('sandbox', 'sub_sandbox_%');

        $deleted = $qb->getQuery()->execute();

        $this->logger->info('Sandbox data cleaned', ['deleted' => $deleted]);

        return $deleted;
    }
}
