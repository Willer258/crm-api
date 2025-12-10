<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\WorkspaceMemberRole;
use App\Repository\UserRepository;
use App\Repository\WorkspaceRepository;
use App\Service\WorkspaceResolver;
use App\Service\WorkspaceService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/workspace', name: 'app_workspace_')]
#[IsGranted('ROLE_USER')]
class WorkspaceController extends AbstractController
{
    public function __construct(
        private WorkspaceService $workspaceService,
        private WorkspaceResolver $workspaceResolver,
        private WorkspaceRepository $workspaceRepository,
        private UserRepository $userRepository
    ) {
    }

    /**
     * Create a new workspace
     */
    #[Route('/create', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['name'])) {
            return $this->json([
                'status' => 'error',
                'message' => 'Workspace name is required'
            ], 400);
        }

        /** @var User $user */
        $user = $this->getUser();

        try {
            $workspace = $this->workspaceService->createWorkspace(
                $user,
                $data['name'],
                $data['logo'] ?? null
            );

            return $this->json([
                'status' => 'success',
                'data' => [
                    'id' => $workspace->getId(),
                    'name' => $workspace->getName(),
                    'slug' => $workspace->getSlug(),
                    'logo' => $workspace->getLogo(),
                    'isActive' => $workspace->isActive(),
                    'createdAt' => $workspace->getCreatedAt()->format('Y-m-d H:i:s')
                ]
            ], 201);
        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => 'Failed to create workspace: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * List user's workspaces
     */
    #[Route('/list', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $workspaces = $this->workspaceService->getUserWorkspaces($user);

        $data = array_map(function ($workspace) use ($user) {
            $members = $this->workspaceService->getWorkspaceMembers($workspace);

            return [
                'id' => $workspace->getId(),
                'name' => $workspace->getName(),
                'slug' => $workspace->getSlug(),
                'logo' => $workspace->getLogo(),
                'isActive' => $workspace->isActive(),
                'isCurrent' => $user->getCurrentWorkspace() === $workspace,
                'memberCount' => count($members),
                'createdAt' => $workspace->getCreatedAt()->format('Y-m-d H:i:s')
            ];
        }, $workspaces);

        return $this->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    /**
     * Get workspace details
     */
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $workspace = $this->workspaceRepository->find($id);

        if (!$workspace) {
            return $this->json([
                'status' => 'error',
                'message' => 'Workspace not found'
            ], 404);
        }

        /** @var User $user */
        $user = $this->getUser();

        if (!$this->workspaceService->isMember($workspace, $user)) {
            return $this->json([
                'status' => 'error',
                'message' => 'Access denied'
            ], 403);
        }

        $members = $this->workspaceService->getWorkspaceMembers($workspace);

        return $this->json([
            'status' => 'success',
            'data' => [
                'id' => $workspace->getId(),
                'name' => $workspace->getName(),
                'slug' => $workspace->getSlug(),
                'logo' => $workspace->getLogo(),
                'isActive' => $workspace->isActive(),
                'members' => array_map(fn($member) => [
                    'id' => $member->getId(),
                    'user' => [
                        'id' => $member->getUser()->getId(),
                        'email' => $member->getUser()->getEmail()
                    ],
                    'role' => $member->getRole()->value,
                    'isActive' => $member->isActive(),
                    'joinedAt' => $member->getJoinedAt()->format('Y-m-d H:i:s')
                ], $members),
                'createdAt' => $workspace->getCreatedAt()->format('Y-m-d H:i:s'),
                'updatedAt' => $workspace->getUpdatedAt()->format('Y-m-d H:i:s')
            ]
        ]);
    }

    /**
     * Switch to a different workspace
     */
    #[Route('/switch/{id}', name: 'switch', methods: ['POST'])]
    public function switch(int $id): JsonResponse
    {
        $workspace = $this->workspaceRepository->find($id);

        if (!$workspace) {
            return $this->json([
                'status' => 'error',
                'message' => 'Workspace not found'
            ], 404);
        }

        /** @var User $user */
        $user = $this->getUser();

        if (!$this->workspaceService->isMember($workspace, $user)) {
            return $this->json([
                'status' => 'error',
                'message' => 'You are not a member of this workspace'
            ], 403);
        }

        $user->setCurrentWorkspace($workspace);
        $this->userRepository->save($user, true);

        return $this->json([
            'status' => 'success',
            'data' => [
                'id' => $workspace->getId(),
                'name' => $workspace->getName(),
                'slug' => $workspace->getSlug()
            ]
        ]);
    }

    /**
     * Invite user to workspace
     */
    #[Route('/{id}/invite', name: 'invite', methods: ['POST'])]
    public function invite(int $id, Request $request): JsonResponse
    {
        $workspace = $this->workspaceRepository->find($id);

        if (!$workspace) {
            return $this->json([
                'status' => 'error',
                'message' => 'Workspace not found'
            ], 404);
        }

        /** @var User $currentUser */
        $currentUser = $this->getUser();

        if (!$this->workspaceService->canManageWorkspace($workspace, $currentUser)) {
            return $this->json([
                'status' => 'error',
                'message' => 'You do not have permission to invite users'
            ], 403);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['email'])) {
            return $this->json([
                'status' => 'error',
                'message' => 'Email is required'
            ], 400);
        }

        $user = $this->userRepository->findOneBy(['email' => $data['email']]);

        if (!$user) {
            return $this->json([
                'status' => 'error',
                'message' => 'User not found'
            ], 404);
        }

        $role = isset($data['role'])
            ? WorkspaceMemberRole::from($data['role'])
            : WorkspaceMemberRole::MEMBER;

        try {
            $member = $this->workspaceService->inviteUser($workspace, $user, $role);

            return $this->json([
                'status' => 'success',
                'data' => [
                    'id' => $member->getId(),
                    'user' => [
                        'id' => $user->getId(),
                        'email' => $user->getEmail()
                    ],
                    'role' => $member->getRole()->value,
                    'joinedAt' => $member->getJoinedAt()->format('Y-m-d H:i:s')
                ]
            ], 201);
        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Remove user from workspace
     */
    #[Route('/{id}/member/{userId}', name: 'remove_member', methods: ['DELETE'])]
    public function removeMember(int $id, int $userId): JsonResponse
    {
        $workspace = $this->workspaceRepository->find($id);

        if (!$workspace) {
            return $this->json([
                'status' => 'error',
                'message' => 'Workspace not found'
            ], 404);
        }

        /** @var User $currentUser */
        $currentUser = $this->getUser();

        if (!$this->workspaceService->canManageWorkspace($workspace, $currentUser)) {
            return $this->json([
                'status' => 'error',
                'message' => 'You do not have permission to remove users'
            ], 403);
        }

        $user = $this->userRepository->find($userId);

        if (!$user) {
            return $this->json([
                'status' => 'error',
                'message' => 'User not found'
            ], 404);
        }

        try {
            $this->workspaceService->removeUser($workspace, $user);

            return $this->json([
                'status' => 'success',
                'message' => 'User removed from workspace'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Update workspace
     */
    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $workspace = $this->workspaceRepository->find($id);

        if (!$workspace) {
            return $this->json([
                'status' => 'error',
                'message' => 'Workspace not found'
            ], 404);
        }

        /** @var User $user */
        $user = $this->getUser();

        if (!$this->workspaceService->canManageWorkspace($workspace, $user)) {
            return $this->json([
                'status' => 'error',
                'message' => 'You do not have permission to update this workspace'
            ], 403);
        }

        $data = json_decode($request->getContent(), true);

        try {
            $workspace = $this->workspaceService->updateWorkspace($workspace, $data);

            return $this->json([
                'status' => 'success',
                'data' => [
                    'id' => $workspace->getId(),
                    'name' => $workspace->getName(),
                    'slug' => $workspace->getSlug(),
                    'logo' => $workspace->getLogo(),
                    'isActive' => $workspace->isActive(),
                    'updatedAt' => $workspace->getUpdatedAt()->format('Y-m-d H:i:s')
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
