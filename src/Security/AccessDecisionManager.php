<?php

namespace App\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Strategy\AccessDecisionStrategyInterface;
use Symfony\Component\Security\Core\Authorization\Strategy\AffirmativeStrategy;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Simplified Access Decision Manager for workspace-based CRM
 * No more multi-tenant role suffixes, uses standard Symfony roles
 */
final class AccessDecisionManager implements AccessDecisionManagerInterface
{
    private AccessDecisionStrategyInterface $strategy;

    public function __construct(?AccessDecisionStrategyInterface $strategy = null)
    {
        $this->strategy = $strategy ?? new AffirmativeStrategy();
    }

    public function decide(TokenInterface $token, array $attributes, $object = null): bool
    {
        // Empty attributes means public access
        if (empty($attributes)) {
            return true;
        }

        $user = $token->getUser();

        // No user means not authenticated
        if (!$user instanceof UserInterface) {
            // Check if PUBLIC_ACCESS is in attributes
            foreach ($attributes as $attribute) {
                if (str_contains(strtoupper((string)$attribute), 'PUBLIC')) {
                    return true;
                }
            }
            return false;
        }

        // Check if user has any of the required roles
        $userRoles = $user->getRoles();

        foreach ($attributes as $attribute) {
            // PUBLIC_ACCESS always grants access
            if (str_contains(strtoupper((string)$attribute), 'PUBLIC')) {
                return true;
            }

            // Check if user has this role
            if (in_array($attribute, $userRoles, true)) {
                return true;
            }
        }

        // Check role hierarchy (ROLE_SUPER_ADMIN has all access)
        if (in_array('ROLE_SUPER_ADMIN', $userRoles, true)) {
            return true;
        }

        return false;
    }
}
