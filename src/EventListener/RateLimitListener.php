<?php

namespace App\EventListener;

use App\Entity\Quota;
use App\Managers\QuotaManager;
use App\MultiTenancy\Zone;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Psr\Log\LoggerInterface;

/**
 * Rate limiting listener based on tenant quotas
 * Enforces API call limits per tenant according to their subscription plan
 */
#[AsEventListener(event: KernelEvents::REQUEST, priority: 10)]
class RateLimitListener
{
    private const RATE_LIMIT_HEADER_LIMIT = 'X-RateLimit-Limit';
    private const RATE_LIMIT_HEADER_REMAINING = 'X-RateLimit-Remaining';
    private const RATE_LIMIT_HEADER_RESET = 'X-RateLimit-Reset';

    public function __construct(
        private QuotaManager $quotaManager,
        private Zone $zone,
        private LoggerInterface $logger,
        private bool $rateLimitEnabled = true
    ) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        // Only process main requests
        if (!$event->isMainRequest()) {
            return;
        }

        // Skip if rate limiting is disabled
        if (!$this->rateLimitEnabled) {
            return;
        }

        $request = $event->getRequest();

        // Skip rate limiting for certain routes
        $route = $request->attributes->get('_route');
        if ($this->shouldSkipRateLimit($route)) {
            return;
        }

        // Get tenant ID
        $tenantId = $this->getTenantId($request);

        if (!$tenantId) {
            // No tenant ID, skip rate limiting
            return;
        }

        try {
            // Check if API call is allowed
            $canMakeCall = $this->quotaManager->canMakeApiCall($tenantId);

            if (!$canMakeCall) {
                // Rate limit exceeded
                $this->logger->warning('Rate limit exceeded', [
                    'tenant_id' => $tenantId,
                    'route' => $route,
                    'ip' => $request->getClientIp()
                ]);

                $quota = $this->quotaManager->getQuota($tenantId, Quota::TYPE_API_CALLS);

                $response = new JsonResponse([
                    'status' => 'error',
                    'message' => 'Rate limit exceeded',
                    'error_code' => 'RATE_LIMIT_EXCEEDED',
                    'details' => [
                        'limit' => $quota->getLimitValue(),
                        'reset_at' => $this->getResetTime($quota)->format('Y-m-d H:i:s')
                    ]
                ], 429);

                // Add rate limit headers
                $this->addRateLimitHeaders($response, $quota);

                $event->setResponse($response);
                return;
            }

            // Record API call
            $this->quotaManager->recordApiCall($tenantId);

            // Get updated quota
            $quota = $this->quotaManager->getQuota($tenantId, Quota::TYPE_API_CALLS);

            // Add rate limit headers to response (will be added in response event)
            $request->attributes->set('_rate_limit_quota', $quota);
            $request->attributes->set('_rate_limit_tenant', $tenantId);

        } catch (\Exception $e) {
            $this->logger->error('Rate limit check failed', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage()
            ]);

            // On error, allow the request but log it
        }
    }

    /**
     * Determine if route should skip rate limiting
     */
    private function shouldSkipRateLimit(?string $route): bool
    {
        if (!$route) {
            return false;
        }

        $skipRoutes = [
            'app_login',
            'app_register',
            'app_webhook_stripe',
            'api_health_check',
            '_wdt', // Symfony profiler
            '_profiler' // Symfony profiler
        ];

        foreach ($skipRoutes as $skipRoute) {
            if (str_starts_with($route, $skipRoute)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get tenant ID from request
     */
    private function getTenantId($request): ?string
    {
        // Try to get from zone (already set by KernelListener)
        $tenantId = $this->zone->getCurrent();

        if ($tenantId && $tenantId !== 'wiassur') {
            return $tenantId;
        }

        // Fallback to header
        $tenantId = $request->headers->get('X-Tenant-ID');

        if ($tenantId) {
            return $tenantId;
        }

        // Fallback to query parameter
        return $request->query->get('tenant');
    }

    /**
     * Add rate limit headers to response
     */
    private function addRateLimitHeaders(JsonResponse $response, Quota $quota): void
    {
        $limit = $quota->getLimitValue() ?? 0;
        $remaining = $quota->getRemainingQuota() ?? 0;
        $reset = $this->getResetTime($quota)->getTimestamp();

        $response->headers->set(self::RATE_LIMIT_HEADER_LIMIT, (string) $limit);
        $response->headers->set(self::RATE_LIMIT_HEADER_REMAINING, (string) max(0, $remaining));
        $response->headers->set(self::RATE_LIMIT_HEADER_RESET, (string) $reset);
    }

    /**
     * Get reset time for quota (next day at midnight)
     */
    private function getResetTime(Quota $quota): \DateTimeImmutable
    {
        $lastReset = $quota->getLastResetAt() ?? new \DateTimeImmutable();

        // Reset at midnight
        return $lastReset->modify('tomorrow midnight');
    }
}

/**
 * Listener to add rate limit headers to responses
 */
#[AsEventListener(event: KernelEvents::RESPONSE, priority: 0)]
class RateLimitResponseListener
{
    public function onKernelResponse($event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();

        // Check if rate limit data was set
        $quota = $request->attributes->get('_rate_limit_quota');

        if (!$quota || !$quota instanceof Quota) {
            return;
        }

        // Add rate limit headers
        $limit = $quota->getLimitValue() ?? 0;
        $remaining = $quota->getRemainingQuota() ?? 0;
        $resetTime = $this->getResetTime($quota)->getTimestamp();

        $response->headers->set('X-RateLimit-Limit', (string) $limit);
        $response->headers->set('X-RateLimit-Remaining', (string) max(0, $remaining));
        $response->headers->set('X-RateLimit-Reset', (string) $resetTime);
    }

    private function getResetTime(Quota $quota): \DateTimeImmutable
    {
        $lastReset = $quota->getLastResetAt() ?? new \DateTimeImmutable();
        return $lastReset->modify('tomorrow midnight');
    }
}
