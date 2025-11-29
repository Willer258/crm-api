<?php

namespace App\Controller;

use App\Managers\SubscriptionManager;
use App\Managers\BillingManager;
use App\Repository\SubscriptionRepository;
use App\Repository\InvoiceRepository;
use App\Repository\PaymentRepository;
use App\Service\StripeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

#[Route('/webhooks/stripe', name: 'stripe_webhook_')]
final class StripeWebhookController extends AbstractController
{
    public function __construct(
        private StripeService $stripeService,
        private SubscriptionManager $subscriptionManager,
        private BillingManager $billingManager,
        private SubscriptionRepository $subscriptionRepository,
        private InvoiceRepository $invoiceRepository,
        private PaymentRepository $paymentRepository,
        private LoggerInterface $logger,
        private string $stripeWebhookSecret
    ) {}

    /**
     * Stripe webhook endpoint
     */
    #[Route('', name: 'handler', methods: ['POST'])]
    public function handleWebhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->headers->get('Stripe-Signature');

        if (!$signature) {
            $this->logger->error('Stripe webhook missing signature');
            return $this->json(['error' => 'Missing signature'], 400);
        }

        try {
            $event = $this->stripeService->constructWebhookEvent(
                $payload,
                $signature,
                $this->stripeWebhookSecret
            );
        } catch (\Exception $e) {
            $this->logger->error('Stripe webhook signature verification failed', [
                'error' => $e->getMessage()
            ]);
            return $this->json(['error' => 'Invalid signature'], 400);
        }

        $this->logger->info('Stripe webhook received', [
            'type' => $event->type,
            'id' => $event->id
        ]);

        // Handle the event based on type
        try {
            match ($event->type) {
                // Subscription events
                'customer.subscription.created' => $this->handleSubscriptionCreated($event),
                'customer.subscription.updated' => $this->handleSubscriptionUpdated($event),
                'customer.subscription.deleted' => $this->handleSubscriptionDeleted($event),
                'customer.subscription.trial_will_end' => $this->handleTrialWillEnd($event),

                // Invoice events
                'invoice.created' => $this->handleInvoiceCreated($event),
                'invoice.finalized' => $this->handleInvoiceFinalized($event),
                'invoice.paid' => $this->handleInvoicePaid($event),
                'invoice.payment_failed' => $this->handleInvoicePaymentFailed($event),
                'invoice.payment_action_required' => $this->handlePaymentActionRequired($event),

                // Payment events
                'payment_intent.succeeded' => $this->handlePaymentIntentSucceeded($event),
                'payment_intent.payment_failed' => $this->handlePaymentIntentFailed($event),

                // Customer events
                'customer.created' => $this->handleCustomerCreated($event),
                'customer.updated' => $this->handleCustomerUpdated($event),
                'customer.deleted' => $this->handleCustomerDeleted($event),

                // Payment method events
                'payment_method.attached' => $this->handlePaymentMethodAttached($event),
                'payment_method.detached' => $this->handlePaymentMethodDetached($event),

                default => $this->handleUnknownEvent($event)
            };

            return $this->json(['status' => 'success'], 200);

        } catch (\Exception $e) {
            $this->logger->error('Stripe webhook handler failed', [
                'type' => $event->type,
                'error' => $e->getMessage()
            ]);
            return $this->json(['error' => 'Handler failed'], 500);
        }
    }

    /**
     * Handle subscription.created
     */
    private function handleSubscriptionCreated(\Stripe\Event $event): void
    {
        $stripeSubscription = $event->data->object;

        $this->logger->info('Subscription created in Stripe', [
            'stripe_subscription_id' => $stripeSubscription->id,
            'customer' => $stripeSubscription->customer,
            'status' => $stripeSubscription->status
        ]);

        // Subscription should already exist in our DB (created before Stripe call)
        // Just log for now
    }

    /**
     * Handle subscription.updated
     */
    private function handleSubscriptionUpdated(\Stripe\Event $event): void
    {
        $stripeSubscription = $event->data->object;

        $subscription = $this->subscriptionRepository->findOneBy([
            'stripeSubscriptionId' => $stripeSubscription->id
        ]);

        if (!$subscription) {
            $this->logger->warning('Subscription not found for Stripe subscription', [
                'stripe_subscription_id' => $stripeSubscription->id
            ]);
            return;
        }

        // Update status
        $newStatus = match ($stripeSubscription->status) {
            'active' => \App\Entity\Subscription::STATUS_ACTIVE,
            'past_due' => \App\Entity\Subscription::STATUS_PAST_DUE,
            'canceled' => \App\Entity\Subscription::STATUS_CANCELED,
            'unpaid' => \App\Entity\Subscription::STATUS_UNPAID,
            'trialing' => \App\Entity\Subscription::STATUS_TRIALING,
            default => $subscription->getStatus()
        };

        if ($subscription->getStatus() !== $newStatus) {
            match ($newStatus) {
                \App\Entity\Subscription::STATUS_ACTIVE => $this->subscriptionManager->activateSubscription($subscription),
                \App\Entity\Subscription::STATUS_PAST_DUE => $this->subscriptionManager->markAsPastDue($subscription),
                \App\Entity\Subscription::STATUS_CANCELED => $subscription->setStatus($newStatus),
                default => null
            };
        }

        // Update periods
        $subscription->setCurrentPeriodStart(
            \DateTimeImmutable::createFromFormat('U', $stripeSubscription->current_period_start)
        );
        $subscription->setCurrentPeriodEnd(
            \DateTimeImmutable::createFromFormat('U', $stripeSubscription->current_period_end)
        );

        // Update cancel info
        if ($stripeSubscription->cancel_at_period_end) {
            $subscription->setCancelAtPeriodEnd(true);
            if ($stripeSubscription->cancel_at) {
                $subscription->setCancelsAt(
                    \DateTimeImmutable::createFromFormat('U', $stripeSubscription->cancel_at)
                );
            }
        }

        $subscription->setUpdatedAt(new \DateTimeImmutable());
        $this->subscriptionRepository->getEntityManager()->flush();

        $this->logger->info('Subscription updated from Stripe', [
            'subscription_id' => $subscription->getId(),
            'new_status' => $newStatus
        ]);
    }

    /**
     * Handle subscription.deleted
     */
    private function handleSubscriptionDeleted(\Stripe\Event $event): void
    {
        $stripeSubscription = $event->data->object;

        $subscription = $this->subscriptionRepository->findOneBy([
            'stripeSubscriptionId' => $stripeSubscription->id
        ]);

        if (!$subscription) {
            return;
        }

        $subscription->setStatus(\App\Entity\Subscription::STATUS_CANCELED);
        $subscription->setCanceledAt(new \DateTimeImmutable());
        $subscription->setEndDate(new \DateTimeImmutable());
        $subscription->setUpdatedAt(new \DateTimeImmutable());

        $this->subscriptionRepository->getEntityManager()->flush();

        $this->logger->info('Subscription deleted in Stripe', [
            'subscription_id' => $subscription->getId()
        ]);
    }

    /**
     * Handle subscription.trial_will_end
     */
    private function handleTrialWillEnd(\Stripe\Event $event): void
    {
        $stripeSubscription = $event->data->object;

        $subscription = $this->subscriptionRepository->findOneBy([
            'stripeSubscriptionId' => $stripeSubscription->id
        ]);

        if (!$subscription) {
            return;
        }

        // TODO: Send email notification that trial is ending
        $this->logger->info('Trial ending soon', [
            'subscription_id' => $subscription->getId(),
            'trial_ends_at' => $subscription->getTrialEndsAt()?->format('Y-m-d')
        ]);
    }

    /**
     * Handle invoice.created
     */
    private function handleInvoiceCreated(\Stripe\Event $event): void
    {
        $stripeInvoice = $event->data->object;

        $this->logger->info('Invoice created in Stripe', [
            'stripe_invoice_id' => $stripeInvoice->id,
            'amount' => $stripeInvoice->amount_due / 100
        ]);
    }

    /**
     * Handle invoice.finalized
     */
    private function handleInvoiceFinalized(\Stripe\Event $event): void
    {
        $stripeInvoice = $event->data->object;

        $this->logger->info('Invoice finalized in Stripe', [
            'stripe_invoice_id' => $stripeInvoice->id
        ]);
    }

    /**
     * Handle invoice.paid
     */
    private function handleInvoicePaid(\Stripe\Event $event): void
    {
        $stripeInvoice = $event->data->object;

        // Find subscription by Stripe subscription ID
        if (!$stripeInvoice->subscription) {
            $this->logger->warning('Invoice paid but no subscription found', [
                'stripe_invoice_id' => $stripeInvoice->id
            ]);
            return;
        }

        $subscription = $this->subscriptionRepository->findOneBy([
            'stripeSubscriptionId' => $stripeInvoice->subscription
        ]);

        if (!$subscription) {
            $this->logger->warning('Subscription not found for invoice', [
                'stripe_subscription_id' => $stripeInvoice->subscription
            ]);
            return;
        }

        // Find or create invoice
        $invoice = $this->invoiceRepository->findOneBy([
            'stripeInvoiceId' => $stripeInvoice->id
        ]);

        if (!$invoice) {
            // Create invoice from Stripe data
            $invoice = $this->billingManager->createInvoice($subscription);
            $invoice->setInvoiceNumber($stripeInvoice->number ?? $invoice->getInvoiceNumber());
        }

        // Mark as paid
        $this->billingManager->markInvoiceAsPaid($invoice, $stripeInvoice->id);

        // Create payment record if has payment intent
        if ($stripeInvoice->payment_intent) {
            $payment = $this->paymentRepository->findOneBy([
                'stripePaymentIntentId' => $stripeInvoice->payment_intent
            ]);

            if (!$payment) {
                $this->billingManager->createPayment(
                    $invoice,
                    $stripeInvoice->amount_paid / 100,
                    \App\Entity\Payment::METHOD_CARD,
                    \App\Entity\Payment::STATUS_SUCCEEDED,
                    $stripeInvoice->payment_intent
                );
            }
        }

        // Activate subscription if it was in trial or past_due
        if (in_array($subscription->getStatus(), [
            \App\Entity\Subscription::STATUS_TRIALING,
            \App\Entity\Subscription::STATUS_PAST_DUE
        ])) {
            $this->subscriptionManager->activateSubscription($subscription);
        }

        $this->logger->info('Invoice marked as paid', [
            'invoice_id' => $invoice->getId(),
            'stripe_invoice_id' => $stripeInvoice->id
        ]);
    }

    /**
     * Handle invoice.payment_failed
     */
    private function handleInvoicePaymentFailed(\Stripe\Event $event): void
    {
        $stripeInvoice = $event->data->object;

        if (!$stripeInvoice->subscription) {
            return;
        }

        $subscription = $this->subscriptionRepository->findOneBy([
            'stripeSubscriptionId' => $stripeInvoice->subscription
        ]);

        if (!$subscription) {
            return;
        }

        // Mark subscription as past_due
        $this->subscriptionManager->markAsPastDue($subscription);

        // TODO: Send email notification of failed payment

        $this->logger->warning('Invoice payment failed', [
            'subscription_id' => $subscription->getId(),
            'stripe_invoice_id' => $stripeInvoice->id
        ]);
    }

    /**
     * Handle invoice.payment_action_required
     */
    private function handlePaymentActionRequired(\Stripe\Event $event): void
    {
        $stripeInvoice = $event->data->object;

        // TODO: Send email to customer to complete payment authentication

        $this->logger->info('Payment action required', [
            'stripe_invoice_id' => $stripeInvoice->id
        ]);
    }

    /**
     * Handle payment_intent.succeeded
     */
    private function handlePaymentIntentSucceeded(\Stripe\Event $event): void
    {
        $paymentIntent = $event->data->object;

        $payment = $this->paymentRepository->findOneBy([
            'stripePaymentIntentId' => $paymentIntent->id
        ]);

        if ($payment) {
            $this->billingManager->updatePaymentStatus(
                $payment,
                \App\Entity\Payment::STATUS_SUCCEEDED
            );
        }

        $this->logger->info('Payment intent succeeded', [
            'payment_intent_id' => $paymentIntent->id
        ]);
    }

    /**
     * Handle payment_intent.payment_failed
     */
    private function handlePaymentIntentFailed(\Stripe\Event $event): void
    {
        $paymentIntent = $event->data->object;

        $payment = $this->paymentRepository->findOneBy([
            'stripePaymentIntentId' => $paymentIntent->id
        ]);

        if ($payment) {
            $this->billingManager->updatePaymentStatus(
                $payment,
                \App\Entity\Payment::STATUS_FAILED,
                $paymentIntent->last_payment_error?->message
            );
        }

        $this->logger->warning('Payment intent failed', [
            'payment_intent_id' => $paymentIntent->id,
            'error' => $paymentIntent->last_payment_error?->message
        ]);
    }

    /**
     * Handle customer.created
     */
    private function handleCustomerCreated(\Stripe\Event $event): void
    {
        $customer = $event->data->object;

        $this->logger->info('Customer created in Stripe', [
            'customer_id' => $customer->id,
            'email' => $customer->email
        ]);
    }

    /**
     * Handle customer.updated
     */
    private function handleCustomerUpdated(\Stripe\Event $event): void
    {
        $customer = $event->data->object;

        $this->logger->info('Customer updated in Stripe', [
            'customer_id' => $customer->id
        ]);
    }

    /**
     * Handle customer.deleted
     */
    private function handleCustomerDeleted(\Stripe\Event $event): void
    {
        $customer = $event->data->object;

        $this->logger->info('Customer deleted in Stripe', [
            'customer_id' => $customer->id
        ]);
    }

    /**
     * Handle payment_method.attached
     */
    private function handlePaymentMethodAttached(\Stripe\Event $event): void
    {
        $paymentMethod = $event->data->object;

        $this->logger->info('Payment method attached', [
            'payment_method_id' => $paymentMethod->id,
            'customer' => $paymentMethod->customer
        ]);
    }

    /**
     * Handle payment_method.detached
     */
    private function handlePaymentMethodDetached(\Stripe\Event $event): void
    {
        $paymentMethod = $event->data->object;

        $this->logger->info('Payment method detached', [
            'payment_method_id' => $paymentMethod->id
        ]);
    }

    /**
     * Handle unknown event types
     */
    private function handleUnknownEvent(\Stripe\Event $event): void
    {
        $this->logger->info('Unknown Stripe webhook event', [
            'type' => $event->type,
            'id' => $event->id
        ]);
    }
}
