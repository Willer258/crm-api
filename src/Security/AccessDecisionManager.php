<?php

namespace App\Security;

use App\Controller\AdminController;
use App\MultiTenancy\Zone;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Strategy\AccessDecisionStrategyInterface;
use Symfony\Component\Security\Core\Authorization\Strategy\AffirmativeStrategy;
use Symfony\Component\Security\Core\Authorization\Voter\CacheableVoterInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class AccessDecisionManager implements AccessDecisionManagerInterface
{

    private $strategy;

    public function __construct(private Zone                  $zone, ?AccessDecisionStrategyInterface $strategy = null,
                                private UrlGeneratorInterface $router, private RequestStack $requestStack, private ParameterBagInterface $bag
    )
    {
        $this->strategy = $strategy ?? new AffirmativeStrategy();
    }


    public function decide(TokenInterface $token, array $attributes, $object = null): bool
    {
        // ⚠️ SECURITY WARNING: Authentication is currently DISABLED for development
        // TODO: Remove this line and uncomment the code below to enable proper authentication
        // TODO: Configure route permissions via /admin/save/route/requirements before activating
        return true;

        // --- AUTHENTICATION CODE (Currently disabled) ---
//        dump('attributes => ', $attributes);

        $admin = 'ROLE_ADMIN_' . strtoupper($this->zone->getCurrent());
        $request = $this->requestStack->getMainRequest();

        if ($request) {
            $routeName = $request->attributes->get('_route');
            $route = AdminController::getRoute($this->router, $routeName, $this->zone);
            $user = $token->getUser();
            if ($user instanceof UserInterface && in_array($admin, $user->getRoles())) {
                return true;
            }

            if (!isset($route['roles'])) {
                throw new AccessDeniedException();
            }
            if (empty($route['roles'])) {
                throw new AccessDeniedException();
            }


            foreach ($attributes as $attribute) {
                $tenantRole = $attribute . '_' . strtoupper($this->zone->getCurrent());
                if ($user instanceof UserInterface) {
                    if (in_array($tenantRole, $user->getRoles()) || str_contains(strtoupper($attribute), 'PUBLIC')) {
//                        dump($tenantRole . ' is granted');
                        return true;
                    }
//                    dump('user exist but dont have role '.$tenantRole);
                    return false;
                } else {
                    if (str_contains(strtoupper($attribute), 'PUBLIC')) {
//                        dump('false 1');
                        return true;
                    }
//                    dump('false 2');
                    return false;
                }
            }
            foreach ($route['roles'] as $roleGroup) {
                foreach ($roleGroup as $role) {
                    $tenantRole = $role['code'] . '_' . strtoupper($this->zone->getCurrent());
                    if (str_contains($tenantRole, 'PUBLIC')) {
                        return true;
                    }
                }
            }
            if (!$user) {
                return false;
            }
            $choice = false;
            foreach ($route['roles'] as $roleGroup) {
                if (empty($roleGroup)) {
                    throw new AccessDeniedException();
                }
                $isGroupOk = true;
                foreach ($roleGroup as $role) {
                    $tenantRole = $role['code'] . '_' . strtoupper($this->zone->getCurrent());
                    if (!$user) {
                        $isGroupOk = false;
                    }
                    if ($user instanceof UserInterface && !in_array($tenantRole, $user->getRoles())) {
                        $isGroupOk = false;
                    }
                }
                if ($isGroupOk === true) {
                    $choice = true;
                    break;
                }
            }
//            dump('adm decision ', $choice);
            return $choice;
        }
        return false;
    }


}
