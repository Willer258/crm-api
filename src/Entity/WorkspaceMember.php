<?php

namespace App\Entity;

use App\Repository\WorkspaceMemberRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WorkspaceMemberRepository::class)]
#[ORM\Table(name: 'workspace_member')]
#[ORM\UniqueConstraint(name: 'unique_workspace_user', columns: ['workspace_id', 'user_id'])]
#[ORM\Index(columns: ['workspace_id'], name: 'idx_workspace')]
#[ORM\Index(columns: ['user_id'], name: 'idx_user')]
#[ORM\Index(columns: ['role'], name: 'idx_role')]
class WorkspaceMember
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Workspace::class, inversedBy: 'members')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Workspace $workspace = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'workspaceMemberships')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(type: 'string', length: 20, enumType: WorkspaceMemberRole::class)]
    private WorkspaceMemberRole $role = WorkspaceMemberRole::MEMBER;

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $joinedAt = null;

    public function __construct()
    {
        $this->joinedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWorkspace(): ?Workspace
    {
        return $this->workspace;
    }

    public function setWorkspace(?Workspace $workspace): static
    {
        $this->workspace = $workspace;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getRole(): WorkspaceMemberRole
    {
        return $this->role;
    }

    public function setRole(WorkspaceMemberRole $role): static
    {
        $this->role = $role;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getJoinedAt(): ?\DateTimeInterface
    {
        return $this->joinedAt;
    }

    public function setJoinedAt(\DateTimeInterface $joinedAt): static
    {
        $this->joinedAt = $joinedAt;
        return $this;
    }

    public function isOwner(): bool
    {
        return $this->role === WorkspaceMemberRole::OWNER;
    }

    public function isAdmin(): bool
    {
        return $this->role === WorkspaceMemberRole::ADMIN || $this->isOwner();
    }

    public function isMember(): bool
    {
        return $this->role === WorkspaceMemberRole::MEMBER;
    }

    public function isViewer(): bool
    {
        return $this->role === WorkspaceMemberRole::VIEWER;
    }
}
