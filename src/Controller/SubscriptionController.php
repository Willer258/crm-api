<?php

namespace App\Controller;

use App\Entity\Subscription;
use App\Managers\SubscriptionManager;
use App\Managers\QuotaManager;
use App\Managers\BillingManager;
use App\Repository\SubscriptionRepository;
use App\Repository\PlanRepository;
use App\Repository\InvoiceRepository;
use App\Repository\PaymentRepository;
use App\Service\StripeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

#[Route('/api/subscription', name: 'api_subscription_')]
final class SubscriptionController extends AbstractController
{
    public function __construct(
        private SubscriptionManager $subscriptionManager,
        private QuotaManager $quotaManager,
        private BillingManager $billingManager,
        private StripeService $stripeService,
        private SubscriptionRepository $subscriptionRepository,
        private PlanRepository $planRepository,
        private InvoiceRepository $invoiceRepository,
        private PaymentRepository $paymentRepository,
        private LoggerInterface $logger
    ) {}

    /**
     * Get current subscription for tenant
     */
    #[Route('/current', name: 'current', methods: ['GET'], options: ['description' => 'Récupère l\'abonnement actuel du tenant'])]
    public function current(Request $request): JsonResponse
    {
        try {
            // Get tenant ID from request header or authenticated user
            $tenantId = $request->headers->get('X-Tenant-ID') ?? 'default_tenant';

            $subscription = $this->subscriptionRepository->findActiveForTenant($tenantId);

            if (!$subscription) {
                return $this->json([
                    'status' => 'success',
                    'data' => null,
                    'message' => 'No active subscription found'
                ], 200);
            }

            return $this->json([
                'status' => 'success',
                'data' => $this->formatSubscriptionData($subscription)
            ], 200);

        } catch (\Exception $e) {
            $this->logger->error('Failed to get current subscription', [
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'status' => 'error',
                'message' => 'Failed to retrieve subscription'
            ], 500);
        }
    }

    /**
     * Subscribe to a plan
     */
    #[Route('/subscribe', name: 'subscribe', methods: ['POST'], options: ['description' => 'Créer un nouvel abonnement'])]
    public function subscribe(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['plan_code'])) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Plan code is required'
                ], 400);
            }

            $tenantId = $request->headers->get('X-Tenant-ID') ?? 'default_tenant';

            // Check if tenant already has active subscription
            $existingSubscription = $this->subscriptionRepository->findActiveForTenant($tenantId);
            if ($existingSubscription) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Tenant already has an active subscription. Use change-plan endpoint to modify.'
                ], 400);
            }

            // Get plan
            $plan = $this->planRepository->findOneBy(['code' => $data['plan_code']]);
            if (!$plan || !$plan->isActive()) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Invalid or inactive plan'
                ], 404);
            }

            // Get user email for Stripe customer
            $userEmail = $data['email'] ?? $request->headers->get('X-User-Email') ?? 'no-email@example.com';

            // Create or get Stripe customer
            $stripeCustomerId = $this->stripeService->createOrGetCustomer($userEmail, [
                'tenant_id' => $tenantId
            ]);

            // Create Stripe subscription
            $stripeData = $this->stripeService->createSubscription(
                $stripeCustomerId,
                $plan,
                $data['payment_method_id'] ?? null,
                $plan->getTrialDays()
            );

            // Create subscription in our DB
            $subscription = $this->subscriptionManager->createSubscription(
                $tenantId,
                $plan,
                $stripeData['id']
            );

            $subscription->setStripeCustomerId($stripeCustomerId);
            $subscription->setCurrentPeriodStart(
                \DateTimeImmutable::createFromFormat('U', $stripeData['current_period_start'])
            );
            $subscription->setCurrentPeriodEnd(
                \DateTimeImmutable::createFromFormat('U', $stripeData['current_period_end'])
            );

            if ($stripeData['trial_end']) {
                $subscription->setTrialEndsAt(
                    \DateTimeImmutable::createFromFormat('U', $stripeData['trial_end'])
                );
                $subscription->setStatus(Subscription::STATUS_TRIALING);
            }

            $this->subscriptionRepository->getEntityManager()->flush();

            // Initialize quotas
            $this->quotaManager->initializeQuotasForSubscription($subscription, $tenantId);

            $this->logger->info('Subscription created', [
                'tenant_id' => $tenantId,
                'plan' => $plan->getCode(),
                'stripe_subscription_id' => $stripeData['id']
            ]);

            return $this->json([
                'status' => 'success',
                'data' => [
                    'subscription' => $this->formatSubscriptionData($subscription),
                    'client_secret' => $stripeData['client_secret'] // For payment confirmation
                ],
                'message' => 'Subscription created successfully'
            ], 201);

        } catch (\Exception $e) {
            $this->logger->error('Failed to create subscription', [
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'status' => 'error',
                'message' => 'Failed to create subscription: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Change subscription plan
     */
    #[Route('/change-plan', name: 'change_plan', methods: ['POST'], options: ['description' => 'Changer de plan d\'abonnement'])]
    public function changePlan(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['new_plan_code'])) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'New plan code is required'
                ], 400);
            }

            $tenantId = $request->headers->get('X-Tenant-ID') ?? 'default_tenant';

            $subscription = $this->subscriptionRepository->findActiveForTenant($tenantId);
            if (!$subscription) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'No active subscription found'
                ], 404);
            }

            $newPlan = $this->planRepository->findOneBy(['code' => $data['new_plan_code']]);
            if (!$newPlan || !$newPlan->isActive()) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Invalid or inactive plan'
                ], 404);
            }

            // Update in Stripe
            if ($subscription->getStripeSubscriptionId()) {
                $this->stripeService->updateSubscription(
                    $subscription->getStripeSubscriptionId(),
                    $newPlan
                );
            }

            // Update in our DB
            $subscription = $this->subscriptionManager->changePlan($subscription, $newPlan);

            $this->logger->info('Plan changed', [
                'tenant_id' => $tenantId,
                'old_plan' => $subscription->getPlan()->getCode(),
                'new_plan' => $newPlan->getCode()
            ]);

            return $this->json([
                'status' => 'success',
                'data' => $this->formatSubscriptionData($subscription),
                'message' => 'Plan changed successfully'
            ], 200);

        } catch (\Exception $e) {
            $this->logger->error('Failed to change plan', [
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'status' => 'error',
                'message' => 'Failed to change plan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel subscription
     */
    #[Route('/cancel', name: 'cancel', methods: ['POST'], options: ['description' => 'Annuler l\'abonnement'])]
    public function cancel(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $immediately = $data['immediately'] ?? false;

            $tenantId = $request->headers->get('X-Tenant-ID') ?? 'default_tenant';

            $subscription = $this->subscriptionRepository->findActiveForTenant($tenantId);
            if (!$subscription) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'No active subscription found'
                ], 404);
            }

            // Cancel in Stripe
            if ($subscription->getStripeSubscriptionId()) {
                $this->stripeService->cancelSubscription(
                    $subscription->getStripeSubscriptionId(),
                    $immediately
                );
            }

            // Cancel in our DB
            $subscription = $this->subscriptionManager->cancelSubscription($subscription, $immediately);

            $this->logger->info('Subscription canceled', [
                'tenant_id' => $tenantId,
                'immediately' => $immediately
            ]);

            return $this->json([
                'status' => 'success',
                'data' => $this->formatSubscriptionData($subscription),
                'message' => $immediately
                    ? 'Subscription canceled immediately'
                    : 'Subscription will be canceled at period end'
            ], 200);

        } catch (\Exception $e) {
            $this->logger->error('Failed to cancel subscription', [
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'status' => 'error',
                'message' => 'Failed to cancel subscription: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reactivate canceled subscription
     */
    #[Route('/reactivate', name: 'reactivate', methods: ['POST'], options: ['description' => 'Réactiver un abonnement annulé'])]
    public function reactivate(Request $request): JsonResponse
    {
        try {
            $tenantId = $request->headers->get('X-Tenant-ID') ?? 'default_tenant';

            $subscription = $this->subscriptionRepository->findOneBy(['tenantId' => $tenantId]);
            if (!$subscription) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'No subscription found'
                ], 404);
            }

            if (!$subscription->isCancelAtPeriodEnd()) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Subscription is not scheduled for cancellation'
                ], 400);
            }

            // Reactivate in Stripe
            if ($subscription->getStripeSubscriptionId()) {
                $this->stripeService->reactivateSubscription(
                    $subscription->getStripeSubscriptionId()
                );
            }

            // Reactivate in our DB
            $subscription = $this->subscriptionManager->reactivateSubscription($subscription);

            $this->logger->info('Subscription reactivated', [
                'tenant_id' => $tenantId
            ]);

            return $this->json([
                'status' => 'success',
                'data' => $this->formatSubscriptionData($subscription),
                'message' => 'Subscription reactivated successfully'
            ], 200);

        } catch (\Exception $e) {
            $this->logger->error('Failed to reactivate subscription', [
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'status' => 'error',
                'message' => 'Failed to reactivate subscription: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get usage and quota information
     */
    #[Route('/usage', name: 'usage', methods: ['GET'], options: ['description' => 'Récupère l\'utilisation et les quotas'])]
    public function usage(Request $request): JsonResponse
    {
        try {
            $tenantId = $request->headers->get('X-Tenant-ID') ?? 'default_tenant';

            $subscription = $this->subscriptionRepository->findActiveForTenant($tenantId);
            if (!$subscription) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'No active subscription found'
                ], 404);
            }

            $usageSummary = $this->quotaManager->getUsageSummary($tenantId);

            return $this->json([
                'status' => 'success',
                'data' => [
                    'subscription' => [
                        'plan' => $subscription->getPlan()->getName(),
                        'status' => $subscription->getStatus(),
                    ],
                    'quotas' => $usageSummary
                ]
            ], 200);

        } catch (\Exception $e) {
            $this->logger->error('Failed to get usage', [
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'status' => 'error',
                'message' => 'Failed to retrieve usage information'
            ], 500);
        }
    }

    /**
     * Get billing history
     */
    #[Route('/billing-history', name: 'billing_history', methods: ['GET'], options: ['description' => 'Récupère l\'historique de facturation'])]
    public function billingHistory(Request $request): JsonResponse
    {
        try {
            $tenantId = $request->headers->get('X-Tenant-ID') ?? 'default_tenant';

            $subscription = $this->subscriptionRepository->findActiveForTenant($tenantId);
            if (!$subscription) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'No subscription found'
                ], 404);
            }

            $invoices = $this->invoiceRepository->findBySubscription($subscription);

            $invoiceData = array_map(function($invoice) {
                return [
                    'id' => $invoice->getId(),
                    'invoice_number' => $invoice->getInvoiceNumber(),
                    'invoice_date' => $invoice->getInvoiceDate()->format('Y-m-d'),
                    'due_date' => $invoice->getDueDate()->format('Y-m-d'),
                    'status' => $invoice->getStatus(),
                    'subtotal' => $invoice->getSubtotal(),
                    'tax' => $invoice->getTax(),
                    'total' => $invoice->getTotal(),
                    'currency' => $invoice->getCurrency(),
                    'paid_at' => $invoice->getPaidAt()?->format('Y-m-d H:i:s'),
                    'stripe_invoice_id' => $invoice->getStripeInvoiceId(),
                    'line_items' => $invoice->getLineItems(),
                ];
            }, $invoices);

            return $this->json([
                'status' => 'success',
                'data' => $invoiceData
            ], 200);

        } catch (\Exception $e) {
            $this->logger->error('Failed to get billing history', [
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'status' => 'error',
                'message' => 'Failed to retrieve billing history'
            ], 500);
        }
    }

    /**
     * Get payment methods
     */
    #[Route('/payment-methods', name: 'payment_methods', methods: ['GET'], options: ['description' => 'Liste les moyens de paiement'])]
    public function paymentMethods(Request $request): JsonResponse
    {
        try {
            $tenantId = $request->headers->get('X-Tenant-ID') ?? 'default_tenant';

            $subscription = $this->subscriptionRepository->findActiveForTenant($tenantId);
            if (!$subscription || !$subscription->getStripeCustomerId()) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'No subscription or Stripe customer found'
                ], 404);
            }

            $paymentMethods = $this->stripeService->listPaymentMethods(
                $subscription->getStripeCustomerId()
            );

            return $this->json([
                'status' => 'success',
                'data' => $paymentMethods
            ], 200);

        } catch (\Exception $e) {
            $this->logger->error('Failed to get payment methods', [
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'status' => 'error',
                'message' => 'Failed to retrieve payment methods'
            ], 500);
        }
    }

    /**
     * Add payment method
     */
    #[Route('/payment-methods/add', name: 'add_payment_method', methods: ['POST'], options: ['description' => 'Ajouter un moyen de paiement'])]
    public function addPaymentMethod(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['payment_method_id'])) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Payment method ID is required'
                ], 400);
            }

            $tenantId = $request->headers->get('X-Tenant-ID') ?? 'default_tenant';

            $subscription = $this->subscriptionRepository->findActiveForTenant($tenantId);
            if (!$subscription || !$subscription->getStripeCustomerId()) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'No subscription or Stripe customer found'
                ], 404);
            }

            $this->stripeService->attachPaymentMethod(
                $data['payment_method_id'],
                $subscription->getStripeCustomerId()
            );

            return $this->json([
                'status' => 'success',
                'message' => 'Payment method added successfully'
            ], 200);

        } catch (\Exception $e) {
            $this->logger->error('Failed to add payment method', [
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'status' => 'error',
                'message' => 'Failed to add payment method: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove payment method
     */
    #[Route('/payment-methods/{paymentMethodId}', name: 'remove_payment_method', methods: ['DELETE'], options: ['description' => 'Supprimer un moyen de paiement'])]
    public function removePaymentMethod(string $paymentMethodId): JsonResponse
    {
        try {
            $this->stripeService->detachPaymentMethod($paymentMethodId);

            return $this->json([
                'status' => 'success',
                'message' => 'Payment method removed successfully'
            ], 200);

        } catch (\Exception $e) {
            $this->logger->error('Failed to remove payment method', [
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'status' => 'error',
                'message' => 'Failed to remove payment method: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Format subscription data for API response
     */
    private function formatSubscriptionData(Subscription $subscription): array
    {
        $plan = $subscription->getPlan();

        return [
            'id' => $subscription->getId(),
            'tenant_id' => $subscription->getTenantId(),
            'status' => $subscription->getStatus(),
            'plan' => [
                'code' => $plan->getCode(),
                'name' => $plan->getName(),
                'price' => $plan->getPrice(),
                'currency' => $plan->getCurrency(),
                'billing_interval' => $plan->getBillingInterval(),
            ],
            'trial' => [
                'is_in_trial' => $subscription->isInTrial(),
                'trial_ends_at' => $subscription->getTrialEndsAt()?->format('Y-m-d H:i:s'),
                'days_remaining' => $subscription->getTrialDaysRemaining(),
            ],
            'billing' => [
                'current_period_start' => $subscription->getCurrentPeriodStart()?->format('Y-m-d H:i:s'),
                'current_period_end' => $subscription->getCurrentPeriodEnd()?->format('Y-m-d H:i:s'),
                'days_until_renewal' => $subscription->getDaysUntilRenewal(),
                'cancel_at_period_end' => $subscription->isCancelAtPeriodEnd(),
                'cancels_at' => $subscription->getCancelsAt()?->format('Y-m-d H:i:s'),
            ],
            'dates' => [
                'start_date' => $subscription->getStartDate()->format('Y-m-d H:i:s'),
                'end_date' => $subscription->getEndDate()?->format('Y-m-d H:i:s'),
                'canceled_at' => $subscription->getCanceledAt()?->format('Y-m-d H:i:s'),
            ],
            'stripe' => [
                'subscription_id' => $subscription->getStripeSubscriptionId(),
                'customer_id' => $subscription->getStripeCustomerId(),
            ],
        ];
    }
}
