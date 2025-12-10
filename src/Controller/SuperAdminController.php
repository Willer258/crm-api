<?php

namespace App\Controller;

use App\Service\SuperAdminService;
use App\Service\SupportTicketService;
use App\Service\SaasMetricsService;
use App\Service\TenantProvisioningService;
use App\Repository\SupportTicketRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Super Admin Controller
 * Requires ROLE_SUPER_ADMIN for all actions
 */
#[Route('/api/super-admin', name: 'api_super_admin_')]
#[IsGranted('ROLE_SUPER_ADMIN')]
class SuperAdminController extends AbstractController
{
    public function __construct(
        private SuperAdminService $superAdminService,
        private SupportTicketService $supportTicketService,
        private SaasMetricsService $metricsService,
        private TenantProvisioningService $provisioningService,
        private SupportTicketRepository $ticketRepository
    ) {}

    /**
     * Get complete platform overview (dashboard)
     */
    #[Route('/dashboard', name: 'dashboard', methods: ['GET'])]
    public function dashboard(): JsonResponse
    {
        $overview = $this->superAdminService->getPlatformOverview();

        return $this->json([
            'status' => 'success',
            'data' => $overview
        ]);
    }

    /**
     * Get all tenants with filters
     */
    #[Route('/tenants', name: 'tenants_list', methods: ['GET'])]
    public function listTenants(Request $request): JsonResponse
    {
        $filters = [
            'status' => $request->query->get('status'),
            'plan_code' => $request->query->get('plan'),
            'search' => $request->query->get('search')
        ];

        $tenants = $this->superAdminService->getTenantsOverview($filters);

        return $this->json([
            'status' => 'success',
            'data' => $tenants,
            'total' => count($tenants)
        ]);
    }

    /**
     * Get detailed info for specific tenant
     */
    #[Route('/tenants/{tenantId}', name: 'tenant_details', methods: ['GET'])]
    public function getTenantDetails(string $tenantId): JsonResponse
    {
        try {
            $details = $this->superAdminService->getTenantDetails($tenantId);

            return $this->json([
                'status' => 'success',
                'data' => $details
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Get system health status
     */
    #[Route('/health', name: 'health', methods: ['GET'])]
    public function getSystemHealth(): JsonResponse
    {
        $health = $this->superAdminService->getSystemHealth();

        return $this->json([
            'status' => 'success',
            'data' => $health
        ]);
    }

    /**
     * Get SaaS metrics
     */
    #[Route('/metrics', name: 'metrics', methods: ['GET'])]
    public function getMetrics(Request $request): JsonResponse
    {
        $type = $request->query->get('type', 'all');

        $metrics = match($type) {
            'mrr' => ['mrr' => $this->metricsService->calculateMRR()],
            'arr' => ['arr' => $this->metricsService->calculateARR()],
            'churn' => ['churn' => $this->metricsService->calculateChurnRate(
                new \DateTimeImmutable('-1 month'),
                new \DateTimeImmutable()
            )],
            'ltv' => ['ltv' => $this->metricsService->calculateLTV()],
            'all' => $this->metricsService->getDashboardMetrics(),
            default => ['error' => 'Invalid metric type']
        };

        return $this->json([
            'status' => 'success',
            'data' => $metrics
        ]);
    }

    /**
     * Export full metrics report
     */
    #[Route('/metrics/export', name: 'metrics_export', methods: ['GET'])]
    public function exportMetrics(): JsonResponse
    {
        $export = $this->metricsService->exportMetrics();

        return $this->json([
            'status' => 'success',
            'data' => $export
        ]);
    }

    /**
     * Provision a new tenant
     */
    #[Route('/tenants/provision', name: 'provision_tenant', methods: ['POST'])]
    public function provisionTenant(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['tenant_id']) || empty($data['plan_code'])) {
            return $this->json([
                'status' => 'error',
                'message' => 'tenant_id and plan_code are required'
            ], 400);
        }

        // Get plan
        $plan = $this->getDoctrine()
            ->getRepository(\App\Entity\Plan::class)
            ->findOneBy(['code' => $data['plan_code']]);

        if (!$plan) {
            return $this->json([
                'status' => 'error',
                'message' => 'Plan not found'
            ], 404);
        }

        $adminData = [
            'email' => $data['admin_email'] ?? 'admin@' . $data['tenant_id'] . '.com',
            'password' => $data['admin_password'] ?? bin2hex(random_bytes(16)),
            'firstName' => $data['admin_first_name'] ?? 'Admin',
            'lastName' => $data['admin_last_name'] ?? 'User'
        ];

        $result = $this->provisioningService->provisionTenant(
            $data['tenant_id'],
            $plan,
            $adminData,
            $data['zone'] ?? 'wiassur'
        );

        return $this->json([
            'status' => $result['success'] ? 'success' : 'error',
            'data' => $result
        ], $result['success'] ? 201 : 400);
    }

    /**
     * Toggle tenant access (enable/disable)
     */
    #[Route('/tenants/{tenantId}/access', name: 'toggle_tenant_access', methods: ['PATCH'])]
    public function toggleTenantAccess(string $tenantId, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $enabled = $data['enabled'] ?? true;
        $reason = $data['reason'] ?? 'Manual action by super admin';

        $result = $this->superAdminService->toggleTenantAccess($tenantId, $enabled, $reason);

        return $this->json([
            'status' => 'success',
            'data' => $result
        ]);
    }

    /**
     * Impersonate tenant (for support)
     */
    #[Route('/tenants/{tenantId}/impersonate', name: 'impersonate_tenant', methods: ['POST'])]
    public function impersonateTenant(string $tenantId, Request $request): JsonResponse
    {
        $user = $this->getUser();
        $adminUserId = $user ? $user->getUserIdentifier() : 'super_admin';

        $result = $this->superAdminService->impersonateTenant($tenantId, $adminUserId);

        return $this->json([
            'status' => 'success',
            'data' => $result
        ]);
    }

    /**
     * Get all support tickets
     */
    #[Route('/support/tickets', name: 'support_tickets', methods: ['GET'])]
    public function listSupportTickets(Request $request): JsonResponse
    {
        $status = $request->query->get('status');
        $priority = $request->query->get('priority');
        $tenantId = $request->query->get('tenant_id');

        $criteria = [];
        if ($status) $criteria['status'] = $status;
        if ($priority) $criteria['priority'] = $priority;
        if ($tenantId) $criteria['tenantId'] = $tenantId;

        $tickets = $this->ticketRepository->findBy(
            $criteria,
            ['createdAt' => 'DESC']
        );

        return $this->json([
            'status' => 'success',
            'data' => $tickets,
            'total' => count($tickets)
        ], 200, [], ['groups' => ['ticket:read']]);
    }

    /**
     * Get specific support ticket
     */
    #[Route('/support/tickets/{id}', name: 'support_ticket_details', methods: ['GET'])]
    public function getSupportTicket(int $id): JsonResponse
    {
        $ticket = $this->ticketRepository->find($id);

        if (!$ticket) {
            return $this->json([
                'status' => 'error',
                'message' => 'Ticket not found'
            ], 404);
        }

        return $this->json([
            'status' => 'success',
            'data' => $ticket
        ], 200, [], ['groups' => ['ticket:detail']]);
    }

    /**
     * Assign ticket to agent
     */
    #[Route('/support/tickets/{id}/assign', name: 'assign_ticket', methods: ['PATCH'])]
    public function assignTicket(int $id, Request $request): JsonResponse
    {
        $ticket = $this->ticketRepository->find($id);

        if (!$ticket) {
            return $this->json([
                'status' => 'error',
                'message' => 'Ticket not found'
            ], 404);
        }

        $data = json_decode($request->getContent(), true);

        $updatedTicket = $this->supportTicketService->assignTicket(
            $ticket,
            $data['agent_user_id'],
            $data['agent_name']
        );

        return $this->json([
            'status' => 'success',
            'data' => $updatedTicket
        ], 200, [], ['groups' => ['ticket:read']]);
    }

    /**
     * Add message to ticket
     */
    #[Route('/support/tickets/{id}/messages', name: 'add_ticket_message', methods: ['POST'])]
    public function addTicketMessage(int $id, Request $request): JsonResponse
    {
        $ticket = $this->ticketRepository->find($id);

        if (!$ticket) {
            return $this->json([
                'status' => 'error',
                'message' => 'Ticket not found'
            ], 404);
        }

        $data = json_decode($request->getContent(), true);

        $message = $this->supportTicketService->addMessage(
            $ticket,
            $data['message'],
            $data['is_from_agent'] ?? true,
            $data['author_name'] ?? null,
            $data['author_email'] ?? null,
            $data['is_internal'] ?? false
        );

        return $this->json([
            'status' => 'success',
            'data' => $message
        ], 201, [], ['groups' => ['ticket:detail']]);
    }

    /**
     * Update ticket status
     */
    #[Route('/support/tickets/{id}/status', name: 'update_ticket_status', methods: ['PATCH'])]
    public function updateTicketStatus(int $id, Request $request): JsonResponse
    {
        $ticket = $this->ticketRepository->find($id);

        if (!$ticket) {
            return $this->json([
                'status' => 'error',
                'message' => 'Ticket not found'
            ], 404);
        }

        $data = json_decode($request->getContent(), true);

        $updatedTicket = $this->supportTicketService->updateStatus(
            $ticket,
            $data['status']
        );

        return $this->json([
            'status' => 'success',
            'data' => $updatedTicket
        ], 200, [], ['groups' => ['ticket:read']]);
    }

    /**
     * Get support statistics
     */
    #[Route('/support/statistics', name: 'support_statistics', methods: ['GET'])]
    public function getSupportStatistics(Request $request): JsonResponse
    {
        $tenantId = $request->query->get('tenant_id');

        $stats = $this->supportTicketService->getStatistics($tenantId);

        return $this->json([
            'status' => 'success',
            'data' => $stats
        ]);
    }

    // ============================================
    // WEB ROUTES (HTML Responses for Admin UI)
    // ============================================

    /**
     * Admin Dashboard (web interface)
     */
    #[Route('/admin/web/dashboard', name: 'admin_dashboard', methods: ['GET'])]
    public function webDashboard(): Response
    {
        $overview = $this->superAdminService->getPlatformOverview();
        $metrics = $this->metricsService->getDashboardMetrics();

        return $this->render('admin/dashboard/index.html.twig', [
            'overview' => $overview,
            'metrics' => $metrics,
        ]);
    }

    /**
     * Tenants list (web interface)
     */
    #[Route('/admin/web/tenants', name: 'admin_tenants_list', methods: ['GET'])]
    public function webTenantsList(Request $request): Response
    {
        $filters = [
            'status' => $request->query->get('status'),
            'plan_code' => $request->query->get('plan'),
            'search' => $request->query->get('search')
        ];

        $tenants = $this->superAdminService->getTenantsOverview($filters);

        return $this->render('admin/tenants/list.html.twig', [
            'tenants' => $tenants,
            'filters' => $filters,
        ]);
    }

    /**
     * Tenant detail (web interface)
     */
    #[Route('/admin/web/tenants/{tenantId}', name: 'admin_tenant_detail', methods: ['GET'])]
    public function webTenantDetail(string $tenantId): Response
    {
        try {
            $tenant = $this->superAdminService->getTenantDetails($tenantId);

            return $this->render('admin/tenants/detail.html.twig', [
                'tenant' => $tenant,
            ]);

        } catch (\Exception $e) {
            $this->addFlash('error', 'Tenant non trouvé: ' . $e->getMessage());
            return $this->redirectToRoute('admin_tenants_list');
        }
    }

    /**
     * Metrics dashboard (web interface)
     */
    #[Route('/admin/web/metrics', name: 'admin_metrics', methods: ['GET'])]
    public function webMetrics(Request $request): Response
    {
        $metrics = $this->metricsService->getDashboardMetrics();
        $revenueTrends = $this->metricsService->getRevenueTrends(12);

        return $this->render('admin/metrics/index.html.twig', [
            'metrics' => $metrics,
            'revenueTrends' => $revenueTrends,
        ]);
    }

    /**
     * Support tickets list (web interface)
     */
    #[Route('/admin/web/support/tickets', name: 'admin_support_tickets', methods: ['GET'])]
    public function webSupportTickets(Request $request): Response
    {
        $status = $request->query->get('status');
        $priority = $request->query->get('priority');
        $tenantId = $request->query->get('tenant_id');

        $criteria = [];
        if ($status) $criteria['status'] = $status;
        if ($priority) $criteria['priority'] = $priority;
        if ($tenantId) $criteria['tenantId'] = $tenantId;

        $tickets = $this->ticketRepository->findBy(
            $criteria,
            ['createdAt' => 'DESC'],
            100
        );

        $stats = $this->supportTicketService->getStatistics($tenantId);

        return $this->render('admin/support/tickets.html.twig', [
            'tickets' => $tickets,
            'stats' => $stats,
            'filters' => $criteria,
        ]);
    }

    /**
     * Support ticket detail (web interface)
     */
    #[Route('/admin/web/support/tickets/{id}', name: 'admin_support_ticket_detail', methods: ['GET'])]
    public function webSupportTicketDetail(int $id): Response
    {
        $ticket = $this->ticketRepository->find($id);

        if (!$ticket) {
            $this->addFlash('error', 'Ticket non trouvé');
            return $this->redirectToRoute('admin_support_tickets');
        }

        return $this->render('admin/support/ticket_detail.html.twig', [
            'ticket' => $ticket,
        ]);
    }

    /**
     * System health monitoring (web interface)
     */
    #[Route('/admin/web/health', name: 'admin_health', methods: ['GET'])]
    public function webHealth(): Response
    {
        $health = $this->superAdminService->getSystemHealth();

        return $this->render('admin/health/index.html.twig', [
            'health' => $health,
        ]);
    }
}
