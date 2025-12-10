<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Workspace;
use App\Entity\WorkspaceMember;
use App\Entity\WorkspaceMemberRole;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WorkspaceMember>
 */
class WorkspaceMemberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkspaceMember::class);
    }

    /**
     * Find member by workspace and user
     */
    public function findByWorkspaceAndUser(Workspace $workspace, User $user): ?WorkspaceMember
    {
        return $this->findOneBy([
            'workspace' => $workspace,
            'user' => $user,
            'isActive' => true
        ]);
    }

    /**
     * Find all members of a workspace
     */
    public function findByWorkspace(Workspace $workspace): array
    {
        return $this->findBy(
            ['workspace' => $workspace, 'isActive' => true],
            ['joinedAt' => 'DESC']
        );
    }

    /**
     * Find all workspaces for a user
     */
    public function findWorkspacesByUser(User $user): array
    {
        return $this->createQueryBuilder('wm')
            ->select('wm', 'w')
            ->join('wm.workspace', 'w')
            ->where('wm.user = :user')
            ->andWhere('wm.isActive = true')
            ->andWhere('w.isActive = true')
            ->setParameter('user', $user)
            ->orderBy('wm.joinedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Check if user is member of workspace
     */
    public function isMember(Workspace $workspace, User $user): bool
    {
        $count = $this->createQueryBuilder('wm')
            ->select('COUNT(wm.id)')
            ->where('wm.workspace = :workspace')
            ->andWhere('wm.user = :user')
            ->andWhere('wm.isActive = true')
            ->setParameter('workspace', $workspace)
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * Check if user has specific role in workspace
     */
    public function hasRole(Workspace $workspace, User $user, WorkspaceMemberRole $role): bool
    {
        $member = $this->findByWorkspaceAndUser($workspace, $user);
        return $member && $member->getRole() === $role;
    }

    /**
     * Find workspace owner
     */
    public function findOwner(Workspace $workspace): ?WorkspaceMember
    {
        return $this->findOneBy([
            'workspace' => $workspace,
            'role' => WorkspaceMemberRole::OWNER,
            'isActive' => true
        ]);
    }
}
