<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Workspace;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Resolves the current workspace for API requests
 */
class WorkspaceResolver
{
    private ?Workspace $currentWorkspace = null;

    public function __construct(
        private RequestStack $requestStack,
        private TokenStorageInterface $tokenStorage
    ) {
    }

    /**
     * Get the current workspace from:
     * 1. Request header X-Workspace-Id
     * 2. User's current workspace
     * 3. User's first workspace
     */
    public function getCurrentWorkspace(): ?Workspace
    {
        if ($this->currentWorkspace !== null) {
            return $this->currentWorkspace;
        }

        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            return null;
        }

        // Try to get workspace from header
        $workspaceId = $request->headers->get('X-Workspace-Id');

        if ($workspaceId) {
            // Workspace will be set by WorkspaceListener
            return $this->currentWorkspace;
        }

        // Get workspace from authenticated user
        $token = $this->tokenStorage->getToken();

        if (!$token) {
            return null;
        }

        $user = $token->getUser();

        if (!$user instanceof User) {
            return null;
        }

        // Return user's current workspace
        $this->currentWorkspace = $user->getCurrentWorkspace();

        // If user has no current workspace, use their first workspace
        if (!$this->currentWorkspace) {
            $workspaces = $user->getWorkspaces();

            if (!empty($workspaces)) {
                $this->currentWorkspace = $workspaces[0];
            }
        }

        return $this->currentWorkspace;
    }

    /**
     * Set the current workspace (used by WorkspaceListener)
     */
    public function setCurrentWorkspace(?Workspace $workspace): void
    {
        $this->currentWorkspace = $workspace;
    }

    /**
     * Get current workspace ID
     */
    public function getCurrentWorkspaceId(): ?int
    {
        $workspace = $this->getCurrentWorkspace();
        return $workspace ? $workspace->getId() : null;
    }

    /**
     * Check if a workspace is set
     */
    public function hasWorkspace(): bool
    {
        return $this->getCurrentWorkspace() !== null;
    }

    /**
     * Clear the current workspace (useful for testing)
     */
    public function clear(): void
    {
        $this->currentWorkspace = null;
    }
}
