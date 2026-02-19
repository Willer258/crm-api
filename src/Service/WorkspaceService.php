<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Workspace;
use App\Entity\WorkspaceMember;
use App\Entity\WorkspaceMemberRole;
use App\Repository\WorkspaceRepository;
use App\Repository\WorkspaceMemberRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class WorkspaceService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WorkspaceRepository $workspaceRepository,
        private WorkspaceMemberRepository $workspaceMemberRepository,
        private SluggerInterface $slugger
    ) {
    }

    /**
     * Get managed user from EntityManager (JWT users don't have ID loaded)
     */
    private function getManagedUser(User $user): ?User
    {
        if ($user->getId() !== null) {
            return $user;
        }
        return $this->entityManager->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);
    }

    /**
     * Create a new workspace with an owner
     */
    public function createWorkspace(User $owner, string $name, ?string $logo = null): Workspace
    {
        $managedOwner = $this->getManagedUser($owner);
        if (!$managedOwner) {
            throw new \InvalidArgumentException('Owner not found');
        }

        $workspace = new Workspace();
        $workspace->setName($name);
        $workspace->setSlug($this->generateUniqueSlug($name));

        if ($logo) {
            $workspace->setLogo($logo);
        }

        $this->entityManager->persist($workspace);

        // Add owner as workspace member
        $member = new WorkspaceMember();
        $member->setWorkspace($workspace);
        $member->setUser($managedOwner);
        $member->setRole(WorkspaceMemberRole::OWNER);
        $member->setIsActive(true);

        $this->entityManager->persist($member);

        // Set as owner's current workspace if they don't have one
        if (!$managedOwner->getCurrentWorkspace()) {
            $managedOwner->setCurrentWorkspace($workspace);
        }

        $this->entityManager->flush();

        return $workspace;
    }

    /**
     * Update workspace details
     */
    public function updateWorkspace(Workspace $workspace, array $data): Workspace
    {
        if (isset($data['name'])) {
            $workspace->setName($data['name']);

            // Update slug if name changed
            if (isset($data['updateSlug']) && $data['updateSlug'] === true) {
                $workspace->setSlug($this->generateUniqueSlug($data['name'], $workspace->getId()));
            }
        }

        if (isset($data['logo'])) {
            $workspace->setLogo($data['logo']);
        }

        if (isset($data['description'])) {
            $workspace->setDescription($data['description']);
        }

        if (isset($data['industry'])) {
            $workspace->setIndustry($data['industry']);
        }

        if (isset($data['employeeCount'])) {
            $workspace->setEmployeeCount($data['employeeCount']);
        }

        if (isset($data['website'])) {
            $workspace->setWebsite($data['website']);
        }

        if (isset($data['phone'])) {
            $workspace->setPhone($data['phone']);
        }

        if (isset($data['email'])) {
            $workspace->setEmail($data['email']);
        }

        if (isset($data['address'])) {
            $workspace->setAddress($data['address']);
        }

        if (isset($data['city'])) {
            $workspace->setCity($data['city']);
        }

        if (isset($data['postalCode'])) {
            $workspace->setPostalCode($data['postalCode']);
        }

        if (isset($data['country'])) {
            $workspace->setCountry($data['country']);
        }

        if (isset($data['siret'])) {
            $workspace->setSiret($data['siret']);
        }

        if (isset($data['vatNumber'])) {
            $workspace->setVatNumber($data['vatNumber']);
        }

        if (isset($data['isActive'])) {
            $workspace->setIsActive($data['isActive']);
        }

        $workspace->setUpdatedAt(new \DateTime());

        $this->entityManager->flush();

        return $workspace;
    }

    /**
     * Invite a user to workspace
     */
    public function inviteUser(Workspace $workspace, User $user, WorkspaceMemberRole $role = WorkspaceMemberRole::MEMBER): WorkspaceMember
    {
        // Check if already a member
        $existingMember = $this->workspaceMemberRepository->findByWorkspaceAndUser($workspace, $user);

        if ($existingMember) {
            // Reactivate if inactive
            if (!$existingMember->isActive()) {
                $existingMember->setIsActive(true);
                $this->entityManager->flush();
            }
            return $existingMember;
        }

        $member = new WorkspaceMember();
        $member->setWorkspace($workspace);
        $member->setUser($user);
        $member->setRole($role);
        $member->setIsActive(true);

        $this->entityManager->persist($member);
        $this->entityManager->flush();

        return $member;
    }

    /**
     * Remove user from workspace
     */
    public function removeUser(Workspace $workspace, User $user): void
    {
        $member = $this->workspaceMemberRepository->findByWorkspaceAndUser($workspace, $user);

        if (!$member) {
            throw new \InvalidArgumentException('User is not a member of this workspace');
        }

        // Don't allow removing the owner
        if ($member->getRole() === WorkspaceMemberRole::OWNER) {
            throw new \InvalidArgumentException('Cannot remove workspace owner');
        }

        $member->setIsActive(false);

        // If this was the user's current workspace, switch to another one
        if ($user->getCurrentWorkspace() === $workspace) {
            $otherWorkspaces = $this->getUserWorkspaces($user);
            $user->setCurrentWorkspace($otherWorkspaces[0] ?? null);
        }

        $this->entityManager->flush();
    }

    /**
     * Change user role in workspace
     */
    public function changeUserRole(Workspace $workspace, User $user, WorkspaceMemberRole $newRole): WorkspaceMember
    {
        $member = $this->workspaceMemberRepository->findByWorkspaceAndUser($workspace, $user);

        if (!$member) {
            throw new \InvalidArgumentException('User is not a member of this workspace');
        }

        // Don't allow changing owner role
        if ($member->getRole() === WorkspaceMemberRole::OWNER) {
            throw new \InvalidArgumentException('Cannot change owner role');
        }

        $member->setRole($newRole);
        $this->entityManager->flush();

        return $member;
    }

    /**
     * Get all workspaces for a user
     */
    public function getUserWorkspaces(User $user): array
    {
        $managedUser = $this->getManagedUser($user);
        if (!$managedUser) {
            return [];
        }

        $memberships = $this->workspaceMemberRepository->findWorkspacesByUser($managedUser);

        return array_map(
            fn(WorkspaceMember $m) => $m->getWorkspace(),
            $memberships
        );
    }

    /**
     * Get all members of a workspace
     */
    public function getWorkspaceMembers(Workspace $workspace): array
    {
        return $this->workspaceMemberRepository->findByWorkspace($workspace);
    }

    /**
     * Check if user can manage workspace (owner or admin)
     */
    public function canManageWorkspace(Workspace $workspace, User $user): bool
    {
        $managedUser = $this->getManagedUser($user);
        if (!$managedUser) {
            return false;
        }

        $member = $this->workspaceMemberRepository->findByWorkspaceAndUser($workspace, $managedUser);

        return $member && $member->getRole()->canManageMembers();
    }

    /**
     * Check if user can edit in workspace
     */
    public function canEdit(Workspace $workspace, User $user): bool
    {
        $managedUser = $this->getManagedUser($user);
        if (!$managedUser) {
            return false;
        }

        $member = $this->workspaceMemberRepository->findByWorkspaceAndUser($workspace, $managedUser);

        return $member && $member->getRole()->canEdit();
    }

    /**
     * Check if user is member of workspace
     */
    public function isMember(Workspace $workspace, User $user): bool
    {
        $managedUser = $this->getManagedUser($user);
        if (!$managedUser) {
            return false;
        }

        return $this->workspaceMemberRepository->isMember($workspace, $managedUser);
    }

    /**
     * Deactivate workspace
     */
    public function deactivateWorkspace(Workspace $workspace): void
    {
        $workspace->setIsActive(false);
        $workspace->setUpdatedAt(new \DateTime());
        $this->entityManager->flush();
    }

    /**
     * Generate unique slug for workspace
     */
    private function generateUniqueSlug(string $name, ?int $excludeId = null): string
    {
        $baseSlug = $this->slugger->slug($name)->lower()->toString();
        $slug = $baseSlug;
        $counter = 1;

        while (!$this->workspaceRepository->isSlugAvailable($slug, $excludeId)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
