<?php

namespace App\Managers;

use App\Entity\Plan;
use App\Entity\Subscription;
use App\Repository\PlanRepository;
use App\Repository\SubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class SubscriptionManager extends Manager
{
    public function __construct(
        EntityManagerInterface $em,
        private SubscriptionRepository $subscriptionRepository,
        private PlanRepository $planRepository,
        private QuotaManager $quotaManager,
        private LoggerInterface $logger
    ) {
        parent::__construct($em, new \App\Utils\Sanitizer(), new \Symfony\Component\Serializer\Serializer([]));
    }

    /**
     * Create a new subscription
     */
    public function createSubscription(
        string $tenantId,
        Plan $plan,
        ?string $stripeSubscriptionId = null,
        ?string $stripeCustomerId = null
    ): Subscription {
        $subscription = new Subscription();
        $subscription->setTenantId($tenantId);
        $subscription->setPlan($plan);
        $subscription->setStatus(Subscription::STATUS_TRIALING);
        $subscription->setStripeSubscriptionId($stripeSubscriptionId);
        $subscription->setStripeCustomerId($stripeCustomerId);

        // Set trial period
        if ($plan->getTrialDays() && $plan->getTrialDays() > 0) {
            $trialEnd = new \DateTimeImmutable("+{$plan->getTrialDays()} days");
            $subscription->setTrialEndsAt($trialEnd);
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

        $this->em->persist($subscription);
        $this->em->flush();

        // Initialize quotas
        $this->quotaManager->initializeQuotasForSubscription($subscription, $tenantId);

        $this->logger->info('Subscription created', [
            'tenant_id' => $tenantId,
            'plan' => $plan->getName(),
            'subscription_id' => $subscription->getId()
        ]);

        return $subscription;
    }

    /**
     * Upgrade/downgrade subscription
     */
    public function changePlan(Subscription $subscription, Plan $newPlan): Subscription
    {
        $oldPlan = $subscription->getPlan();

        $subscription->setPlan($newPlan);
        $subscription->setUpdatedAt(new \DateTimeImmutable());

        $this->em->flush();

        // Update quotas
        $this->quotaManager->initializeQuotasForSubscription($subscription, $subscription->getTenantId());

        $this->logger->info('Plan changed', [
            'subscription_id' => $subscription->getId(),
            'old_plan' => $oldPlan->getName(),
            'new_plan' => $newPlan->getName()
        ]);

        return $subscription;
    }

    /**
     * Cancel subscription
     */
    public function cancelSubscription(Subscription $subscription, bool $immediately = false): Subscription
    {
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

        $this->logger->info('Subscription canceled', [
            'subscription_id' => $subscription->getId(),
            'immediately' => $immediately
        ]);

        return $subscription;
    }

    /**
     * Reactivate canceled subscription
     */
    public function reactivateSubscription(Subscription $subscription): Subscription
    {
        $subscription->setStatus(Subscription::STATUS_ACTIVE);
        $subscription->setCancelAtPeriodEnd(false);
        $subscription->setCancelsAt(null);
        $subscription->setUpdatedAt(new \DateTimeImmutable());

        $this->em->flush();

        $this->logger->info('Subscription reactivated', [
            'subscription_id' => $subscription->getId()
        ]);

        return $subscription;
    }

    /**
     * Renew subscription
     */
    public function renewSubscription(Subscription $subscription): Subscription
    {
        $plan = $subscription->getPlan();
        $currentEnd = $subscription->getCurrentPeriodEnd();

        $newPeriodEnd = match($plan->getBillingInterval()) {
            'monthly' => $currentEnd->modify('+1 month'),
            'yearly' => $currentEnd->modify('+1 year'),
            default => $currentEnd->modify('+1 month')
        };

        $subscription->setCurrentPeriodStart($currentEnd);
        $subscription->setCurrentPeriodEnd($newPeriodEnd);
        $subscription->setUpdatedAt(new \DateTimeImmutable());

        $this->em->flush();

        $this->logger->info('Subscription renewed', [
            'subscription_id' => $subscription->getId(),
            'new_period_end' => $newPeriodEnd->format('Y-m-d')
        ]);

        return $subscription;
    }

    /**
     * Mark subscription as past due
     */
    public function markAsPastDue(Subscription $subscription): Subscription
    {
        $subscription->setStatus(Subscription::STATUS_PAST_DUE);
        $subscription->setUpdatedAt(new \DateTimeImmutable());

        $this->em->flush();

        $this->logger->warning('Subscription marked as past due', [
            'subscription_id' => $subscription->getId(),
            'tenant_id' => $subscription->getTenantId()
        ]);

        return $subscription;
    }

    /**
     * Activate subscription (after payment)
     */
    public function activateSubscription(Subscription $subscription): Subscription
    {
        $subscription->setStatus(Subscription::STATUS_ACTIVE);
        $subscription->setUpdatedAt(new \DateTimeImmutable());

        $this->em->flush();

        $this->logger->info('Subscription activated', [
            'subscription_id' => $subscription->getId()
        ]);

        return $subscription;
    }

    /**
     * Get active subscription for tenant
     */
    public function getActiveSubscription(string $tenantId): ?Subscription
    {
        return $this->subscriptionRepository->findActiveForTenant($tenantId);
    }

    /**
     * Check if tenant has active subscription
     */
    public function hasActiveSubscription(string $tenantId): bool
    {
        return $this->getActiveSubscription($tenantId) !== null;
    }

    /**
     * Get available plans for upgrade
     */
    public function getUpgradeOptions(Subscription $subscription): array
    {
        $currentPlan = $subscription->getPlan();
        $allPlans = $this->planRepository->findBy(['isActive' => true, 'isPublic' => true], ['sortOrder' => 'ASC']);

        return array_filter($allPlans, fn(Plan $plan) => $plan->getPrice() > $currentPlan->getPrice());
    }

    /**
     * Get subscription statistics
     */
    public function getStatistics(): array
    {
        return $this->subscriptionRepository->getStatistics();
    }

    /**
     * Calculate MRR
     */
    public function calculateMRR(): float
    {
        return $this->subscriptionRepository->calculateMRR();
    }
}
