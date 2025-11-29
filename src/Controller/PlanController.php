<?php

namespace App\Controller;

use App\Repository\PlanRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

#[Route('/api/plans', name: 'api_plans_')]
final class PlanController extends AbstractController
{
    public function __construct(
        private PlanRepository $planRepository,
        private LoggerInterface $logger
    ) {}

    /**
     * List all available subscription plans
     */
    #[Route('/list', name: 'list', methods: ['GET'], options: ['description' => 'Liste tous les plans disponibles'])]
    public function list(): JsonResponse
    {
        try {
            $plans = $this->planRepository->findAllActive();

            $planData = array_map(function($plan) {
                return [
                    'id' => $plan->getId(),
                    'code' => $plan->getCode(),
                    'name' => $plan->getName(),
                    'description' => $plan->getDescription(),
                    'price' => $plan->getPrice(),
                    'currency' => $plan->getCurrency(),
                    'billing_interval' => $plan->getBillingInterval(),
                    'trial_days' => $plan->getTrialDays(),
                    'is_active' => $plan->isActive(),
                    'is_popular' => $plan->isPopular(),
                    'display_order' => $plan->getDisplayOrder(),
                    'quotas' => [
                        'max_contacts' => $plan->getMaxContacts(),
                        'max_companies' => $plan->getMaxCompanies(),
                        'max_deals' => $plan->getMaxDeals(),
                        'max_users' => $plan->getMaxUsers(),
                        'max_storage_bytes' => $plan->getMaxStorageBytes(),
                        'max_storage_mb' => $plan->getMaxStorageBytes() ? round($plan->getMaxStorageBytes() / 1024 / 1024, 2) : null,
                        'max_api_calls_per_day' => $plan->getMaxApiCallsPerDay(),
                        'max_email_sends_per_month' => $plan->getMaxEmailSendsPerMonth(),
                        'max_sms_sends_per_month' => $plan->getMaxSmsSendsPerMonth(),
                    ],
                    'features' => $plan->getFeatures(),
                    'created_at' => $plan->getCreatedAt()->format('Y-m-d H:i:s'),
                ];
            }, $plans);

            return $this->json([
                'status' => 'success',
                'data' => $planData
            ], 200);

        } catch (\Exception $e) {
            $this->logger->error('Failed to list plans', [
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'status' => 'error',
                'message' => 'Failed to retrieve plans'
            ], 500);
        }
    }

    /**
     * Get plan details by code
     */
    #[Route('/{code}', name: 'show', methods: ['GET'], options: ['description' => 'Récupère les détails d\'un plan par son code'])]
    public function show(string $code): JsonResponse
    {
        try {
            $plan = $this->planRepository->findOneBy(['code' => $code]);

            if (!$plan) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Plan not found'
                ], 404);
            }

            if (!$plan->isActive()) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Plan is not available'
                ], 404);
            }

            return $this->json([
                'status' => 'success',
                'data' => [
                    'id' => $plan->getId(),
                    'code' => $plan->getCode(),
                    'name' => $plan->getName(),
                    'description' => $plan->getDescription(),
                    'price' => $plan->getPrice(),
                    'currency' => $plan->getCurrency(),
                    'billing_interval' => $plan->getBillingInterval(),
                    'trial_days' => $plan->getTrialDays(),
                    'is_active' => $plan->isActive(),
                    'is_popular' => $plan->isPopular(),
                    'display_order' => $plan->getDisplayOrder(),
                    'quotas' => [
                        'max_contacts' => $plan->getMaxContacts(),
                        'max_companies' => $plan->getMaxCompanies(),
                        'max_deals' => $plan->getMaxDeals(),
                        'max_users' => $plan->getMaxUsers(),
                        'max_storage_bytes' => $plan->getMaxStorageBytes(),
                        'max_storage_mb' => $plan->getMaxStorageBytes() ? round($plan->getMaxStorageBytes() / 1024 / 1024, 2) : null,
                        'max_api_calls_per_day' => $plan->getMaxApiCallsPerDay(),
                        'max_email_sends_per_month' => $plan->getMaxEmailSendsPerMonth(),
                        'max_sms_sends_per_month' => $plan->getMaxSmsSendsPerMonth(),
                    ],
                    'features' => $plan->getFeatures(),
                    'stripe_product_id' => $plan->getStripeProductId(),
                    'stripe_price_id' => $plan->getStripePriceId(),
                    'created_at' => $plan->getCreatedAt()->format('Y-m-d H:i:s'),
                    'updated_at' => $plan->getUpdatedAt()?->format('Y-m-d H:i:s'),
                ]
            ], 200);

        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve plan', [
                'code' => $code,
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'status' => 'error',
                'message' => 'Failed to retrieve plan details'
            ], 500);
        }
    }

    /**
     * Compare plans (useful for pricing page)
     */
    #[Route('/compare', name: 'compare', methods: ['GET'], options: ['description' => 'Compare tous les plans disponibles'])]
    public function compare(): JsonResponse
    {
        try {
            $plans = $this->planRepository->findAllActive();

            $comparison = [
                'plans' => [],
                'features_matrix' => []
            ];

            // Collect all unique features
            $allFeatures = [];
            foreach ($plans as $plan) {
                $allFeatures = array_merge($allFeatures, $plan->getFeatures());
            }
            $allFeatures = array_unique($allFeatures);

            // Build plans data
            foreach ($plans as $plan) {
                $planData = [
                    'code' => $plan->getCode(),
                    'name' => $plan->getName(),
                    'description' => $plan->getDescription(),
                    'price' => $plan->getPrice(),
                    'currency' => $plan->getCurrency(),
                    'billing_interval' => $plan->getBillingInterval(),
                    'is_popular' => $plan->isPopular(),
                    'quotas' => [
                        'contacts' => $plan->getMaxContacts(),
                        'companies' => $plan->getMaxCompanies(),
                        'deals' => $plan->getMaxDeals(),
                        'users' => $plan->getMaxUsers(),
                        'storage_mb' => $plan->getMaxStorageBytes() ? round($plan->getMaxStorageBytes() / 1024 / 1024, 2) : null,
                        'api_calls_per_day' => $plan->getMaxApiCallsPerDay(),
                    ],
                    'features' => $plan->getFeatures(),
                ];

                $comparison['plans'][] = $planData;

                // Build feature matrix
                foreach ($allFeatures as $feature) {
                    if (!isset($comparison['features_matrix'][$feature])) {
                        $comparison['features_matrix'][$feature] = [];
                    }
                    $comparison['features_matrix'][$feature][$plan->getCode()] = in_array($feature, $plan->getFeatures());
                }
            }

            return $this->json([
                'status' => 'success',
                'data' => $comparison
            ], 200);

        } catch (\Exception $e) {
            $this->logger->error('Failed to compare plans', [
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'status' => 'error',
                'message' => 'Failed to compare plans'
            ], 500);
        }
    }
}
