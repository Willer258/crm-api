<?php

namespace App\Service;

use App\Entity\Subscription;
use App\Entity\DunningAttempt;
use App\Repository\SubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

/**
 * Dunning service for managing failed payments and subscription recovery
 *
 * Dunning Workflow:
 * - Day 1: Email notification of failed payment
 * - Day 3: Reminder email with payment link
 * - Day 7: Restrict access to read-only mode
 * - Day 14: Suspend account completely
 * - Day 30: Schedule data deletion
 */
class DunningService
{
    public function __construct(
        private EntityManagerInterface $em,
        private SubscriptionRepository $subscriptionRepository,
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        private string $supportEmail = 'support@example.com'
    ) {}

    /**
     * Process dunning for all past due subscriptions
     */
    public function processDunning(): array
    {
        $this->logger->info('Starting dunning process');

        $pastDueSubscriptions = $this->subscriptionRepository->findBy([
            'status' => Subscription::STATUS_PAST_DUE
        ]);

        $stats = [
            'total_past_due' => count($pastDueSubscriptions),
            'notifications_sent' => 0,
            'reminders_sent' => 0,
            'accounts_restricted' => 0,
            'accounts_suspended' => 0,
            'deletions_scheduled' => 0,
            'errors' => 0
        ];

        foreach ($pastDueSubscriptions as $subscription) {
            try {
                $result = $this->processDunningForSubscription($subscription);

                // Update stats
                if (isset($stats[$result['action_taken']])) {
                    $stats[$result['action_taken']]++;
                }

            } catch (\Exception $e) {
                $stats['errors']++;
                $this->logger->error('Dunning processing failed', [
                    'subscription_id' => $subscription->getId(),
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->logger->info('Dunning process completed', $stats);

        return $stats;
    }

    /**
     * Process dunning for a specific subscription
     */
    public function processDunningForSubscription(Subscription $subscription): array
    {
        $this->logger->info('Processing dunning for subscription', [
            'subscription_id' => $subscription->getId(),
            'tenant_id' => $subscription->getTenantId()
        ]);

        // Calculate days past due
        $daysPastDue = $this->getDaysPastDue($subscription);

        // Determine action based on days past due
        $action = $this->determineAction($daysPastDue);

        // Check if action was already taken
        if ($this->wasActionTaken($subscription, $action)) {
            $this->logger->debug('Action already taken', [
                'subscription_id' => $subscription->getId(),
                'action' => $action
            ]);

            return [
                'action_taken' => 'none',
                'reason' => 'already_processed'
            ];
        }

        // Execute action
        $result = $this->executeAction($subscription, $action, $daysPastDue);

        // Record attempt
        $this->recordDunningAttempt($subscription, $action, $result);

        return [
            'action_taken' => $action,
            'days_past_due' => $daysPastDue,
            'result' => $result
        ];
    }

    /**
     * Calculate days past due
     */
    private function getDaysPastDue(Subscription $subscription): int
    {
        $updatedAt = $subscription->getUpdatedAt() ?? $subscription->getCreatedAt();
        $now = new \DateTimeImmutable();

        return (int) $updatedAt->diff($now)->days;
    }

    /**
     * Determine action based on days past due
     */
    private function determineAction(int $daysPastDue): string
    {
        return match(true) {
            $daysPastDue >= 30 => DunningAttempt::ACTION_SCHEDULE_DELETION,
            $daysPastDue >= 14 => DunningAttempt::ACTION_SUSPEND_ACCOUNT,
            $daysPastDue >= 7 => DunningAttempt::ACTION_RESTRICT_ACCESS,
            $daysPastDue >= 3 => DunningAttempt::ACTION_EMAIL_REMINDER,
            default => DunningAttempt::ACTION_EMAIL_NOTIFICATION
        };
    }

    /**
     * Check if action was already taken
     */
    private function wasActionTaken(Subscription $subscription, string $action): bool
    {
        $attempt = $this->em->getRepository(DunningAttempt::class)->findOneBy([
            'subscription' => $subscription,
            'action' => $action,
            'status' => DunningAttempt::STATUS_SENT
        ]);

        return $attempt !== null;
    }

    /**
     * Execute dunning action
     */
    private function executeAction(Subscription $subscription, string $action, int $daysPastDue): array
    {
        $this->logger->info('Executing dunning action', [
            'subscription_id' => $subscription->getId(),
            'action' => $action,
            'days_past_due' => $daysPastDue
        ]);

        return match($action) {
            DunningAttempt::ACTION_EMAIL_NOTIFICATION => $this->sendInitialNotification($subscription),
            DunningAttempt::ACTION_EMAIL_REMINDER => $this->sendReminder($subscription, $daysPastDue),
            DunningAttempt::ACTION_RESTRICT_ACCESS => $this->restrictAccess($subscription),
            DunningAttempt::ACTION_SUSPEND_ACCOUNT => $this->suspendAccount($subscription),
            DunningAttempt::ACTION_SCHEDULE_DELETION => $this->scheduleDeletion($subscription),
            default => ['success' => false, 'message' => 'Unknown action']
        };
    }

    /**
     * Send initial payment failure notification
     */
    private function sendInitialNotification(Subscription $subscription): array
    {
        $tenantId = $subscription->getTenantId();
        $plan = $subscription->getPlan();

        $email = (new Email())
            ->from($this->supportEmail)
            ->to($tenantId . '@example.com') // Replace with actual tenant email
            ->subject('Payment Failed - Action Required')
            ->html($this->getEmailTemplate('initial_notification', [
                'tenant_id' => $tenantId,
                'plan_name' => $plan->getName(),
                'amount' => $plan->getFormattedPrice(),
                'update_payment_url' => $this->getUpdatePaymentUrl($subscription)
            ]));

        try {
            $this->mailer->send($email);

            $this->logger->info('Initial notification sent', [
                'subscription_id' => $subscription->getId(),
                'tenant_id' => $tenantId
            ]);

            return ['success' => true, 'message' => 'Initial notification sent'];

        } catch (\Exception $e) {
            $this->logger->error('Failed to send initial notification', [
                'error' => $e->getMessage()
            ]);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Send payment reminder
     */
    private function sendReminder(Subscription $subscription, int $daysPastDue): array
    {
        $tenantId = $subscription->getTenantId();
        $plan = $subscription->getPlan();

        $email = (new Email())
            ->from($this->supportEmail)
            ->to($tenantId . '@example.com')
            ->subject('Urgent: Payment Required - ' . $daysPastDue . ' Days Overdue')
            ->html($this->getEmailTemplate('reminder', [
                'tenant_id' => $tenantId,
                'plan_name' => $plan->getName(),
                'amount' => $plan->getFormattedPrice(),
                'days_past_due' => $daysPastDue,
                'update_payment_url' => $this->getUpdatePaymentUrl($subscription),
                'grace_period_end' => (new \DateTimeImmutable('+4 days'))->format('Y-m-d')
            ]));

        try {
            $this->mailer->send($email);

            $this->logger->info('Reminder sent', [
                'subscription_id' => $subscription->getId(),
                'days_past_due' => $daysPastDue
            ]);

            return ['success' => true, 'message' => 'Reminder sent'];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Restrict account to read-only mode
     */
    private function restrictAccess(Subscription $subscription): array
    {
        $tenantId = $subscription->getTenantId();

        // TODO: Implement actual access restriction logic
        // This would involve setting tenant permissions to read-only

        $this->logger->warning('Access restricted for tenant', [
            'tenant_id' => $tenantId,
            'subscription_id' => $subscription->getId()
        ]);

        // Send notification
        $email = (new Email())
            ->from($this->supportEmail)
            ->to($tenantId . '@example.com')
            ->subject('Account Restricted - Payment Required')
            ->html($this->getEmailTemplate('restrict_access', [
                'tenant_id' => $tenantId,
                'update_payment_url' => $this->getUpdatePaymentUrl($subscription)
            ]));

        try {
            $this->mailer->send($email);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send restriction email', [
                'error' => $e->getMessage()
            ]);
        }

        return ['success' => true, 'message' => 'Access restricted to read-only'];
    }

    /**
     * Suspend account completely
     */
    private function suspendAccount(Subscription $subscription): array
    {
        $tenantId = $subscription->getTenantId();

        // TODO: Implement actual suspension logic
        // This would disable all access to the tenant account

        $this->logger->warning('Account suspended', [
            'tenant_id' => $tenantId,
            'subscription_id' => $subscription->getId()
        ]);

        // Send notification
        $email = (new Email())
            ->from($this->supportEmail)
            ->to($tenantId . '@example.com')
            ->subject('Account Suspended - Immediate Action Required')
            ->html($this->getEmailTemplate('suspend_account', [
                'tenant_id' => $tenantId,
                'deletion_date' => (new \DateTimeImmutable('+16 days'))->format('Y-m-d'),
                'update_payment_url' => $this->getUpdatePaymentUrl($subscription)
            ]));

        try {
            $this->mailer->send($email);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send suspension email', [
                'error' => $e->getMessage()
            ]);
        }

        return ['success' => true, 'message' => 'Account suspended'];
    }

    /**
     * Schedule account deletion
     */
    private function scheduleDeletion(Subscription $subscription): array
    {
        $tenantId = $subscription->getTenantId();
        $deletionDate = new \DateTimeImmutable('+7 days');

        // TODO: Create deletion job/queue entry

        $this->logger->critical('Account deletion scheduled', [
            'tenant_id' => $tenantId,
            'subscription_id' => $subscription->getId(),
            'deletion_date' => $deletionDate->format('Y-m-d')
        ]);

        // Send final notification
        $email = (new Email())
            ->from($this->supportEmail)
            ->to($tenantId . '@example.com')
            ->subject('FINAL NOTICE: Account Will Be Deleted')
            ->html($this->getEmailTemplate('schedule_deletion', [
                'tenant_id' => $tenantId,
                'deletion_date' => $deletionDate->format('Y-m-d'),
                'export_data_url' => $this->getExportDataUrl($subscription),
                'update_payment_url' => $this->getUpdatePaymentUrl($subscription)
            ]));

        try {
            $this->mailer->send($email);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send deletion notice', [
                'error' => $e->getMessage()
            ]);
        }

        return ['success' => true, 'message' => 'Deletion scheduled for ' . $deletionDate->format('Y-m-d')];
    }

    /**
     * Record dunning attempt
     */
    private function recordDunningAttempt(Subscription $subscription, string $action, array $result): void
    {
        $attemptNumber = $this->getNextAttemptNumber($subscription);

        $attempt = new DunningAttempt();
        $attempt->setSubscription($subscription);
        $attempt->setAttemptNumber($attemptNumber);
        $attempt->setAction($action);
        $attempt->setScheduledAt(new \DateTimeImmutable());
        $attempt->setMetadata($result);

        if ($result['success']) {
            $attempt->markAsExecuted($result['message']);
        } else {
            $attempt->markAsFailed($result['message']);
        }

        $this->em->persist($attempt);
        $this->em->flush();
    }

    /**
     * Get next attempt number for subscription
     */
    private function getNextAttemptNumber(Subscription $subscription): int
    {
        $lastAttempt = $this->em->getRepository(DunningAttempt::class)
            ->findOneBy(
                ['subscription' => $subscription],
                ['attemptNumber' => 'DESC']
            );

        return $lastAttempt ? $lastAttempt->getAttemptNumber() + 1 : 1;
    }

    /**
     * Get email template
     */
    private function getEmailTemplate(string $template, array $data): string
    {
        // TODO: Use proper Twig templates
        return match($template) {
            'initial_notification' => "
                <h1>Payment Failed</h1>
                <p>Dear {$data['tenant_id']},</p>
                <p>We were unable to process your payment for {$data['plan_name']} ({$data['amount']}).</p>
                <p>Please update your payment method to continue using our service.</p>
                <p><a href='{$data['update_payment_url']}'>Update Payment Method</a></p>
            ",
            'reminder' => "
                <h1>Payment Reminder - {$data['days_past_due']} Days Overdue</h1>
                <p>Your payment of {$data['amount']} is {$data['days_past_due']} days overdue.</p>
                <p>Please update your payment method by {$data['grace_period_end']} to avoid service interruption.</p>
                <p><a href='{$data['update_payment_url']}'>Update Payment Method</a></p>
            ",
            'restrict_access' => "
                <h1>Account Restricted</h1>
                <p>Your account has been restricted to read-only mode due to unpaid balance.</p>
                <p>Update your payment method to restore full access.</p>
                <p><a href='{$data['update_payment_url']}'>Update Payment Method</a></p>
            ",
            'suspend_account' => "
                <h1>Account Suspended</h1>
                <p>Your account has been suspended. All access is disabled.</p>
                <p>Data will be deleted on {$data['deletion_date']} if payment is not received.</p>
                <p><a href='{$data['update_payment_url']}'>Update Payment Method</a></p>
            ",
            'schedule_deletion' => "
                <h1>FINAL NOTICE: Account Deletion Scheduled</h1>
                <p>Your account and all data will be permanently deleted on {$data['deletion_date']}.</p>
                <p>This is your last opportunity to save your data.</p>
                <p><a href='{$data['export_data_url']}'>Export Your Data</a></p>
                <p><a href='{$data['update_payment_url']}'>Update Payment Method to Prevent Deletion</a></p>
            ",
            default => '<p>Default email template</p>'
        };
    }

    /**
     * Get update payment URL
     */
    private function getUpdatePaymentUrl(Subscription $subscription): string
    {
        // TODO: Generate actual URL
        return "https://app.example.com/billing/update-payment?subscription=" . $subscription->getId();
    }

    /**
     * Get export data URL
     */
    private function getExportDataUrl(Subscription $subscription): string
    {
        return "https://app.example.com/export?tenant=" . $subscription->getTenantId();
    }

    /**
     * Resolve dunning (payment succeeded)
     */
    public function resolveDunning(Subscription $subscription): void
    {
        $this->logger->info('Resolving dunning', [
            'subscription_id' => $subscription->getId()
        ]);

        // Mark all pending attempts as resolved
        $qb = $this->em->createQueryBuilder();
        $qb->update(DunningAttempt::class, 'd')
           ->set('d.status', ':resolved')
           ->set('d.resolvedAt', ':now')
           ->where('d.subscription = :subscription')
           ->andWhere('d.status IN (:statuses)')
           ->setParameter('resolved', DunningAttempt::STATUS_RESOLVED)
           ->setParameter('now', new \DateTimeImmutable())
           ->setParameter('subscription', $subscription)
           ->setParameter('statuses', [DunningAttempt::STATUS_PENDING, DunningAttempt::STATUS_SENT])
           ->getQuery()
           ->execute();

        // TODO: Restore access if restricted/suspended

        $this->logger->info('Dunning resolved', [
            'subscription_id' => $subscription->getId()
        ]);
    }
}
