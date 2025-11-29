<?php

namespace App\Service;

use App\Entity\Plan;
use App\Entity\Subscription;
use App\Entity\Invoice;
use App\Entity\Payment;
use Psr\Log\LoggerInterface;
use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;

class StripeService
{
    private StripeClient $stripe;

    public function __construct(
        private string $stripeSecretKey,
        private LoggerInterface $logger
    ) {
        $this->stripe = new StripeClient($this->stripeSecretKey);
    }

    /**
     * Create or retrieve Stripe customer
     */
    public function createOrGetCustomer(string $email, array $metadata = []): string
    {
        try {
            // Try to find existing customer
            $customers = $this->stripe->customers->all([
                'email' => $email,
                'limit' => 1
            ]);

            if (!empty($customers->data)) {
                $customerId = $customers->data[0]->id;
                $this->logger->info('Stripe customer found', ['customer_id' => $customerId]);
                return $customerId;
            }

            // Create new customer
            $customer = $this->stripe->customers->create([
                'email' => $email,
                'metadata' => $metadata
            ]);

            $this->logger->info('Stripe customer created', ['customer_id' => $customer->id]);

            return $customer->id;

        } catch (ApiErrorException $e) {
            $this->logger->error('Stripe customer creation failed', [
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException('Failed to create Stripe customer: ' . $e->getMessage());
        }
    }

    /**
     * Create subscription in Stripe
     */
    public function createSubscription(
        string $customerId,
        Plan $plan,
        ?string $paymentMethodId = null,
        int $trialDays = 0
    ): array {
        try {
            $params = [
                'customer' => $customerId,
                'items' => [
                    ['price' => $plan->getStripePriceId()]
                ],
                'metadata' => [
                    'plan_code' => $plan->getCode(),
                    'plan_name' => $plan->getName()
                ]
            ];

            // Add trial period
            if ($trialDays > 0) {
                $params['trial_period_days'] = $trialDays;
            }

            // Add payment method if provided
            if ($paymentMethodId) {
                $params['default_payment_method'] = $paymentMethodId;
            }

            // Expand to get full subscription data
            $params['expand'] = ['latest_invoice.payment_intent'];

            $subscription = $this->stripe->subscriptions->create($params);

            $this->logger->info('Stripe subscription created', [
                'subscription_id' => $subscription->id,
                'customer_id' => $customerId,
                'plan' => $plan->getCode()
            ]);

            return [
                'id' => $subscription->id,
                'status' => $subscription->status,
                'current_period_start' => $subscription->current_period_start,
                'current_period_end' => $subscription->current_period_end,
                'trial_end' => $subscription->trial_end,
                'client_secret' => $subscription->latest_invoice?->payment_intent?->client_secret
            ];

        } catch (ApiErrorException $e) {
            $this->logger->error('Stripe subscription creation failed', [
                'error' => $e->getMessage(),
                'customer_id' => $customerId
            ]);
            throw new \RuntimeException('Failed to create Stripe subscription: ' . $e->getMessage());
        }
    }

    /**
     * Update subscription (change plan)
     */
    public function updateSubscription(string $subscriptionId, Plan $newPlan): array
    {
        try {
            $subscription = $this->stripe->subscriptions->retrieve($subscriptionId);

            $subscription = $this->stripe->subscriptions->update($subscriptionId, [
                'items' => [
                    [
                        'id' => $subscription->items->data[0]->id,
                        'price' => $newPlan->getStripePriceId()
                    ]
                ],
                'proration_behavior' => 'create_prorations',
                'metadata' => [
                    'plan_code' => $newPlan->getCode(),
                    'plan_name' => $newPlan->getName()
                ]
            ]);

            $this->logger->info('Stripe subscription updated', [
                'subscription_id' => $subscriptionId,
                'new_plan' => $newPlan->getCode()
            ]);

            return [
                'id' => $subscription->id,
                'status' => $subscription->status
            ];

        } catch (ApiErrorException $e) {
            $this->logger->error('Stripe subscription update failed', [
                'error' => $e->getMessage(),
                'subscription_id' => $subscriptionId
            ]);
            throw new \RuntimeException('Failed to update Stripe subscription: ' . $e->getMessage());
        }
    }

    /**
     * Cancel subscription
     */
    public function cancelSubscription(string $subscriptionId, bool $immediately = false): array
    {
        try {
            if ($immediately) {
                $subscription = $this->stripe->subscriptions->cancel($subscriptionId);
            } else {
                $subscription = $this->stripe->subscriptions->update($subscriptionId, [
                    'cancel_at_period_end' => true
                ]);
            }

            $this->logger->info('Stripe subscription canceled', [
                'subscription_id' => $subscriptionId,
                'immediately' => $immediately
            ]);

            return [
                'id' => $subscription->id,
                'status' => $subscription->status,
                'cancel_at_period_end' => $subscription->cancel_at_period_end,
                'canceled_at' => $subscription->canceled_at
            ];

        } catch (ApiErrorException $e) {
            $this->logger->error('Stripe subscription cancellation failed', [
                'error' => $e->getMessage(),
                'subscription_id' => $subscriptionId
            ]);
            throw new \RuntimeException('Failed to cancel Stripe subscription: ' . $e->getMessage());
        }
    }

    /**
     * Reactivate subscription
     */
    public function reactivateSubscription(string $subscriptionId): array
    {
        try {
            $subscription = $this->stripe->subscriptions->update($subscriptionId, [
                'cancel_at_period_end' => false
            ]);

            $this->logger->info('Stripe subscription reactivated', [
                'subscription_id' => $subscriptionId
            ]);

            return [
                'id' => $subscription->id,
                'status' => $subscription->status
            ];

        } catch (ApiErrorException $e) {
            $this->logger->error('Stripe subscription reactivation failed', [
                'error' => $e->getMessage(),
                'subscription_id' => $subscriptionId
            ]);
            throw new \RuntimeException('Failed to reactivate Stripe subscription: ' . $e->getMessage());
        }
    }

    /**
     * Attach payment method to customer
     */
    public function attachPaymentMethod(string $paymentMethodId, string $customerId): void
    {
        try {
            $this->stripe->paymentMethods->attach($paymentMethodId, [
                'customer' => $customerId
            ]);

            // Set as default payment method
            $this->stripe->customers->update($customerId, [
                'invoice_settings' => [
                    'default_payment_method' => $paymentMethodId
                ]
            ]);

            $this->logger->info('Payment method attached', [
                'payment_method_id' => $paymentMethodId,
                'customer_id' => $customerId
            ]);

        } catch (ApiErrorException $e) {
            $this->logger->error('Payment method attachment failed', [
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException('Failed to attach payment method: ' . $e->getMessage());
        }
    }

    /**
     * Detach payment method
     */
    public function detachPaymentMethod(string $paymentMethodId): void
    {
        try {
            $this->stripe->paymentMethods->detach($paymentMethodId);

            $this->logger->info('Payment method detached', [
                'payment_method_id' => $paymentMethodId
            ]);

        } catch (ApiErrorException $e) {
            $this->logger->error('Payment method detachment failed', [
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException('Failed to detach payment method: ' . $e->getMessage());
        }
    }

    /**
     * List customer payment methods
     */
    public function listPaymentMethods(string $customerId): array
    {
        try {
            $paymentMethods = $this->stripe->paymentMethods->all([
                'customer' => $customerId,
                'type' => 'card'
            ]);

            return array_map(function($pm) {
                return [
                    'id' => $pm->id,
                    'type' => $pm->type,
                    'card' => [
                        'brand' => $pm->card->brand,
                        'last4' => $pm->card->last4,
                        'exp_month' => $pm->card->exp_month,
                        'exp_year' => $pm->card->exp_year
                    ]
                ];
            }, $paymentMethods->data);

        } catch (ApiErrorException $e) {
            $this->logger->error('List payment methods failed', [
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException('Failed to list payment methods: ' . $e->getMessage());
        }
    }

    /**
     * Create payment intent
     */
    public function createPaymentIntent(float $amount, string $currency, string $customerId): array
    {
        try {
            $paymentIntent = $this->stripe->paymentIntents->create([
                'amount' => (int)($amount * 100), // Convert to cents
                'currency' => strtolower($currency),
                'customer' => $customerId,
                'automatic_payment_methods' => ['enabled' => true]
            ]);

            $this->logger->info('Payment intent created', [
                'payment_intent_id' => $paymentIntent->id,
                'amount' => $amount,
                'currency' => $currency
            ]);

            return [
                'id' => $paymentIntent->id,
                'client_secret' => $paymentIntent->client_secret,
                'status' => $paymentIntent->status
            ];

        } catch (ApiErrorException $e) {
            $this->logger->error('Payment intent creation failed', [
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException('Failed to create payment intent: ' . $e->getMessage());
        }
    }

    /**
     * Retrieve invoice
     */
    public function retrieveInvoice(string $invoiceId): array
    {
        try {
            $invoice = $this->stripe->invoices->retrieve($invoiceId);

            return [
                'id' => $invoice->id,
                'number' => $invoice->number,
                'status' => $invoice->status,
                'amount_due' => $invoice->amount_due / 100,
                'amount_paid' => $invoice->amount_paid / 100,
                'currency' => strtoupper($invoice->currency),
                'hosted_invoice_url' => $invoice->hosted_invoice_url,
                'invoice_pdf' => $invoice->invoice_pdf,
                'created' => $invoice->created
            ];

        } catch (ApiErrorException $e) {
            $this->logger->error('Invoice retrieval failed', [
                'error' => $e->getMessage(),
                'invoice_id' => $invoiceId
            ]);
            throw new \RuntimeException('Failed to retrieve invoice: ' . $e->getMessage());
        }
    }

    /**
     * Create product in Stripe
     */
    public function createProduct(Plan $plan): string
    {
        try {
            $product = $this->stripe->products->create([
                'name' => $plan->getName(),
                'description' => $plan->getDescription(),
                'metadata' => [
                    'plan_code' => $plan->getCode()
                ]
            ]);

            $this->logger->info('Stripe product created', [
                'product_id' => $product->id,
                'plan_code' => $plan->getCode()
            ]);

            return $product->id;

        } catch (ApiErrorException $e) {
            $this->logger->error('Product creation failed', [
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException('Failed to create product: ' . $e->getMessage());
        }
    }

    /**
     * Create price in Stripe
     */
    public function createPrice(Plan $plan, string $productId): string
    {
        try {
            $price = $this->stripe->prices->create([
                'product' => $productId,
                'unit_amount' => (int)($plan->getPrice() * 100), // Convert to cents
                'currency' => strtolower($plan->getCurrency()),
                'recurring' => [
                    'interval' => $plan->getBillingInterval() === 'yearly' ? 'year' : 'month'
                ],
                'metadata' => [
                    'plan_code' => $plan->getCode()
                ]
            ]);

            $this->logger->info('Stripe price created', [
                'price_id' => $price->id,
                'plan_code' => $plan->getCode()
            ]);

            return $price->id;

        } catch (ApiErrorException $e) {
            $this->logger->error('Price creation failed', [
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException('Failed to create price: ' . $e->getMessage());
        }
    }

    /**
     * Sync plan with Stripe (create product and price)
     */
    public function syncPlanWithStripe(Plan $plan): array
    {
        $productId = $plan->getStripeProductId();

        // Create product if not exists
        if (!$productId) {
            $productId = $this->createProduct($plan);
        }

        // Create price
        $priceId = $this->createPrice($plan, $productId);

        return [
            'product_id' => $productId,
            'price_id' => $priceId
        ];
    }

    /**
     * Construct webhook event (for signature verification)
     */
    public function constructWebhookEvent(string $payload, string $signature, string $secret): \Stripe\Event
    {
        try {
            return \Stripe\Webhook::constructEvent($payload, $signature, $secret);
        } catch (\Exception $e) {
            $this->logger->error('Webhook signature verification failed', [
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException('Invalid webhook signature');
        }
    }
}
