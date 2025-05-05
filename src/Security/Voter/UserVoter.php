<?php

namespace App\Security\Voter;

use App\MultiTenancy\Zone;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Role\RoleHierarchy;
use Symfony\Component\Security\Core\User\UserInterface;

class UserVoter
// extends Voter
{

    public function __construct(private RoleHierarchy $hierarchy, private Zone $zone)
    {

    }

    protected function supports(string $attribute, mixed $subject): bool
    {


        return true;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
//        dd($attribute, $user);
        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            return false;
        }
        dd($attribute);
        $access = $this->hierarchy->getReachableRoleNames($user->getRoles());
        return in_array($attribute, $access) || in_array($attribute . '_' . strtoupper($this->zone->getCurrent()), $access);
    }
}

