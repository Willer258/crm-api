<?php

namespace App\Service;

use App\Entity\OnboardingStep;
use App\Repository\OnboardingStepRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Onboarding service to manage tenant onboarding workflow
 */
class OnboardingService
{
    public function __construct(
        private EntityManagerInterface $em,
        private OnboardingStepRepository $onboardingStepRepository,
        private LoggerInterface $logger
    ) {}

    /**
     * Initialize onboarding steps for a new tenant
     */
    public function initializeOnboarding(string $tenantId): array
    {
        $this->logger->info('Initializing onboarding for tenant', [
            'tenant_id' => $tenantId
        ]);

        $steps = $this->getDefaultSteps();
        $createdSteps = [];

        foreach ($steps as $index => $stepData) {
            $step = new OnboardingStep();
            $step->setTenantId($tenantId);
            $step->setStepType($stepData['type']);
            $step->setStepName($stepData['name']);
            $step->setDescription($stepData['description']);
            $step->setOrderIndex($index + 1);
            $step->setIsRequired($stepData['required']);
            $step->setStatus(OnboardingStep::STATUS_PENDING);

            $this->em->persist($step);
            $createdSteps[] = $step;
        }

        $this->em->flush();

        $this->logger->info('Onboarding initialized', [
            'tenant_id' => $tenantId,
            'steps_created' => count($createdSteps)
        ]);

        return $createdSteps;
    }

    /**
     * Get default onboarding steps
     */
    private function getDefaultSteps(): array
    {
        return [
            [
                'type' => OnboardingStep::STEP_EMAIL_VERIFICATION,
                'name' => 'Verify Email Address',
                'description' => 'Confirm your email address to secure your account',
                'required' => true
            ],
            [
                'type' => OnboardingStep::STEP_COMPANY_PROFILE,
                'name' => 'Set Up Company Profile',
                'description' => 'Add your company information and branding',
                'required' => true
            ],
            [
                'type' => OnboardingStep::STEP_INITIAL_CONFIG,
                'name' => 'Configure Initial Settings',
                'description' => 'Set your timezone, currency, and language preferences',
                'required' => true
            ],
            [
                'type' => OnboardingStep::STEP_DATA_IMPORT,
                'name' => 'Import Your Data',
                'description' => 'Import existing contacts, companies, and deals (optional)',
                'required' => false
            ],
            [
                'type' => OnboardingStep::STEP_INVITE_USERS,
                'name' => 'Invite Team Members',
                'description' => 'Add your team to collaborate in the CRM',
                'required' => false
            ],
            [
                'type' => OnboardingStep::STEP_TOUR_COMPLETED,
                'name' => 'Take the Product Tour',
                'description' => 'Learn the basics of using the CRM',
                'required' => false
            ]
        ];
    }

    /**
     * Get onboarding progress for tenant
     */
    public function getOnboardingProgress(string $tenantId): array
    {
        $steps = $this->onboardingStepRepository->findByTenant($tenantId);

        if (empty($steps)) {
            // No onboarding initialized
            return [
                'initialized' => false,
                'steps' => [],
                'progress' => [
                    'total' => 0,
                    'completed' => 0,
                    'percentage' => 0,
                    'is_complete' => false
                ]
            ];
        }

        $progress = $this->onboardingStepRepository->getProgress($tenantId);

        return [
            'initialized' => true,
            'steps' => $steps,
            'progress' => $progress,
            'next_step' => $this->onboardingStepRepository->getNextPendingStep($tenantId)
        ];
    }

    /**
     * Start a step
     */
    public function startStep(string $tenantId, string $stepType): OnboardingStep
    {
        $step = $this->findStep($tenantId, $stepType);

        if (!$step) {
            throw new \InvalidArgumentException("Step '{$stepType}' not found for tenant");
        }

        $step->markAsStarted();
        $this->em->flush();

        $this->logger->info('Onboarding step started', [
            'tenant_id' => $tenantId,
            'step' => $stepType
        ]);

        return $step;
    }

    /**
     * Complete a step
     */
    public function completeStep(string $tenantId, string $stepType, ?array $metadata = null): OnboardingStep
    {
        $step = $this->findStep($tenantId, $stepType);

        if (!$step) {
            throw new \InvalidArgumentException("Step '{$stepType}' not found for tenant");
        }

        $step->markAsCompleted($metadata);
        $this->em->flush();

        $this->logger->info('Onboarding step completed', [
            'tenant_id' => $tenantId,
            'step' => $stepType,
            'metadata' => $metadata
        ]);

        // Check if onboarding is complete
        if ($this->isOnboardingComplete($tenantId)) {
            $this->onOnboardingComplete($tenantId);
        }

        return $step;
    }

    /**
     * Skip a step (only if not required)
     */
    public function skipStep(string $tenantId, string $stepType): OnboardingStep
    {
        $step = $this->findStep($tenantId, $stepType);

        if (!$step) {
            throw new \InvalidArgumentException("Step '{$stepType}' not found for tenant");
        }

        if ($step->isRequired()) {
            throw new \LogicException("Cannot skip required step '{$stepType}'");
        }

        $step->markAsSkipped();
        $this->em->flush();

        $this->logger->info('Onboarding step skipped', [
            'tenant_id' => $tenantId,
            'step' => $stepType
        ]);

        return $step;
    }

    /**
     * Find a specific step
     */
    private function findStep(string $tenantId, string $stepType): ?OnboardingStep
    {
        return $this->em->getRepository(OnboardingStep::class)->findOneBy([
            'tenantId' => $tenantId,
            'stepType' => $stepType
        ]);
    }

    /**
     * Check if onboarding is complete
     */
    public function isOnboardingComplete(string $tenantId): bool
    {
        return $this->onboardingStepRepository->isOnboardingComplete($tenantId);
    }

    /**
     * Called when onboarding is complete
     */
    private function onOnboardingComplete(string $tenantId): void
    {
        $this->logger->info('Onboarding completed', [
            'tenant_id' => $tenantId
        ]);

        // TODO: Send congratulations email
        // TODO: Unlock full features
        // TODO: Track completion metric
    }

    /**
     * Reset onboarding (for testing)
     */
    public function resetOnboarding(string $tenantId): void
    {
        $this->logger->warning('Resetting onboarding', [
            'tenant_id' => $tenantId
        ]);

        $qb = $this->em->createQueryBuilder();
        $qb->delete(OnboardingStep::class, 'o')
           ->where('o.tenantId = :tenantId')
           ->setParameter('tenantId', $tenantId)
           ->getQuery()
           ->execute();

        $this->logger->info('Onboarding reset', [
            'tenant_id' => $tenantId
        ]);
    }

    /**
     * Get onboarding statistics
     */
    public function getOnboardingStatistics(): array
    {
        $qb = $this->em->createQueryBuilder();

        // Total tenants with onboarding
        $totalTenants = $qb->select('COUNT(DISTINCT o.tenantId)')
            ->from(OnboardingStep::class, 'o')
            ->getQuery()
            ->getSingleScalarResult();

        // Completed onboardings
        $completedQuery = $this->em->createQuery("
            SELECT o.tenantId, COUNT(o.id) as total_steps,
                   SUM(CASE WHEN o.status = 'completed' THEN 1 ELSE 0 END) as completed_steps
            FROM App\Entity\OnboardingStep o
            GROUP BY o.tenantId
        ");

        $results = $completedQuery->getResult();
        $completedCount = 0;

        foreach ($results as $result) {
            if ($result['total_steps'] == $result['completed_steps']) {
                $completedCount++;
            }
        }

        return [
            'total_tenants' => $totalTenants,
            'completed_onboardings' => $completedCount,
            'completion_rate' => $totalTenants > 0
                ? round(($completedCount / $totalTenants) * 100, 2)
                : 0,
            'in_progress' => $totalTenants - $completedCount
        ];
    }

    /**
     * Get step completion rate by type
     */
    public function getStepCompletionRates(): array
    {
        $qb = $this->em->createQueryBuilder();

        $results = $qb->select('o.stepType, o.status, COUNT(o.id) as count')
            ->from(OnboardingStep::class, 'o')
            ->groupBy('o.stepType, o.status')
            ->getQuery()
            ->getResult();

        $rates = [];

        foreach ($results as $result) {
            $stepType = $result['stepType'];
            $status = $result['status'];
            $count = (int) $result['count'];

            if (!isset($rates[$stepType])) {
                $rates[$stepType] = [
                    'total' => 0,
                    'completed' => 0,
                    'skipped' => 0,
                    'pending' => 0,
                    'in_progress' => 0
                ];
            }

            $rates[$stepType]['total'] += $count;

            if ($status === OnboardingStep::STATUS_COMPLETED) {
                $rates[$stepType]['completed'] += $count;
            } elseif ($status === OnboardingStep::STATUS_SKIPPED) {
                $rates[$stepType]['skipped'] += $count;
            } elseif ($status === OnboardingStep::STATUS_PENDING) {
                $rates[$stepType]['pending'] += $count;
            } elseif ($status === OnboardingStep::STATUS_IN_PROGRESS) {
                $rates[$stepType]['in_progress'] += $count;
            }
        }

        // Calculate percentages
        foreach ($rates as $stepType => &$data) {
            $data['completion_rate'] = $data['total'] > 0
                ? round(($data['completed'] / $data['total']) * 100, 2)
                : 0;
        }

        return $rates;
    }
}
