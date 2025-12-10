<?php

namespace App\EventListener;

use App\Entity\User;
use App\Repository\WorkspaceRepository;
use App\Service\WorkspaceResolver;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Listener that resolves and injects the current workspace into requests
 */
#[AsEventListener(event: KernelEvents::REQUEST, priority: 10)]
class WorkspaceListener
{
    public function __construct(
        private WorkspaceResolver $workspaceResolver,
        private WorkspaceRepository $workspaceRepository,
        private TokenStorageInterface $tokenStorage
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // Skip for non-main requests
        if (!$event->isMainRequest()) {
            return;
        }

        // Skip for public routes (login, register, etc.)
        $route = $request->attributes->get('_route');

        if ($this->isPublicRoute($route)) {
            return;
        }

        // Try to resolve workspace from header
        $workspaceId = $request->headers->get('X-Workspace-Id');

        if ($workspaceId) {
            $workspace = $this->workspaceRepository->find($workspaceId);

            if (!$workspace || !$workspace->isActive()) {
                $event->setResponse(new JsonResponse([
                    'status' => 'error',
                    'message' => 'Invalid or inactive workspace'
                ], 400));
                return;
            }

            // Verify user has access to this workspace
            $token = $this->tokenStorage->getToken();

            if ($token) {
                $user = $token->getUser();

                if ($user instanceof User) {
                    $userWorkspaces = $user->getWorkspaces();

                    if (!in_array($workspace, $userWorkspaces, true)) {
                        $event->setResponse(new JsonResponse([
                            'status' => 'error',
                            'message' => 'Access denied to this workspace'
                        ], 403));
                        return;
                    }
                }
            }

            $this->workspaceResolver->setCurrentWorkspace($workspace);
        } else {
            // Use user's current workspace
            $workspace = $this->workspaceResolver->getCurrentWorkspace();

            if (!$workspace) {
                // For authenticated routes, require a workspace
                $token = $this->tokenStorage->getToken();

                if ($token && $token->getUser() instanceof User) {
                    $event->setResponse(new JsonResponse([
                        'status' => 'error',
                        'message' => 'No workspace found. Please create a workspace first.'
                    ], 400));
                    return;
                }
            }
        }

        // Store workspace ID in request attributes for easy access
        if ($workspace) {
            $request->attributes->set('workspace', $workspace);
            $request->attributes->set('workspace_id', $workspace->getId());
        }
    }

    /**
     * Check if route is public (doesn't require workspace)
     */
    private function isPublicRoute(?string $route): bool
    {
        if (!$route) {
            return false;
        }

        $publicRoutes = [
            'api_login',
            'api_register',
            'api_refresh_token',
            'api_forgot_password',
            'api_reset_password',
            '_profiler',
            '_wdt',
        ];

        foreach ($publicRoutes as $publicRoute) {
            if (str_starts_with($route, $publicRoute)) {
                return true;
            }
        }

        return false;
    }
}
