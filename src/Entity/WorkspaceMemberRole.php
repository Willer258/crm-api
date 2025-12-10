<?php

namespace App\Entity;

enum WorkspaceMemberRole: string
{
    case OWNER = 'owner';
    case ADMIN = 'admin';
    case MEMBER = 'member';
    case VIEWER = 'viewer';

    public function getLabel(): string
    {
        return match($this) {
            self::OWNER => 'Owner',
            self::ADMIN => 'Admin',
            self::MEMBER => 'Member',
            self::VIEWER => 'Viewer',
        };
    }

    public function canManageMembers(): bool
    {
        return in_array($this, [self::OWNER, self::ADMIN]);
    }

    public function canEdit(): bool
    {
        return in_array($this, [self::OWNER, self::ADMIN, self::MEMBER]);
    }

    public function canView(): bool
    {
        return true; // All roles can view
    }
}
