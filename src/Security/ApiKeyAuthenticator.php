<?php


namespace App\Security;


use App\Entity\User;
use App\MultiTenancy\Zone;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class ApiKeyAuthenticator extends AbstractAuthenticator
{

    private $allowedSources = ['CORE', 'AUTH', 'FORM', 'MASTER'];

    public function __construct(private ParameterBagInterface $bag, private RouterInterface $router, private Zone $zone,private RequestStack $requestStack)
    {
    }

    /**
     * Does the authenticator support the given Request?
     *
     * If this returns true, authenticate() will be called. If false, the authenticator will be skipped.
     *
     * Returning null means authenticate() can be called lazily when accessing the token storage.
     */
    public function supports(Request $request): ?bool
    {
        return str_starts_with($this->router->getContext()->getPathInfo(), '/service');
    }

    /**
     * Create a passport for the current request.
     *
     * The passport contains the user, credentials and any additional information
     * that has to be checked by the Symfony Security system. For example, a login
     * form authenticator will probably return a passport containing the user, the
     * presented password and the CSRF token value.
     *
     * You may throw any AuthenticationException in this method in case of error (e.g.
     * a UserNotFoundException when the user cannot be found).
     *
     * @return Passport
     *
     */
    public function authenticate(Request $request): \Symfony\Component\Security\Http\Authenticator\Passport\Passport
    {
        $apiToken = $request->headers->get('X-AUTH-TOKEN') ?? $request->get('X-AUTH-TOKEN');
        if (null === $apiToken) {
            throw new \Exception('No API token provided');
        }

        $source = strtoupper($request->headers->get('Source'));

//        if (!in_array($source, $this->allowedSources)) {
//            throw  new \Exception('Source non autorisée');
//        }

        if (!str_starts_with($source, 'SERVICE')) {
            $source = trim('SERVICE ' . $source);
        }
        $timestamp = $request->get('apiKey');
        $when = new \DateTime();
        $when->setTimestamp($timestamp);

        $now = new \DateTime();
        $delta = ($now->getTimestamp() - $when->getTimestamp()) / 60;
        $key = $this->bag->get('SERVICE_API_KEY');
        $prefix = $this->bag->get('SERVICE_PREFIX');
        $suffix = $this->bag->get('SERVICE_SUFFIX');
        $verificationToken = sha1($prefix . $key . $timestamp . $suffix);


        if ($_ENV['APP_ENV'] !== 'dev' && $delta > 1) {
            throw new \Exception('Time out');
        }
        if ($apiToken !== $verificationToken) {
            throw new \Exception('Unauthorized access');
        }

        $userBadge = new UserBadge($apiToken, function () use ($source) {
            $apiUser = new User();
            $apiUser->setEmail($source);
            $roles = [];
            if ($this->requestStack->getCurrentRequest()?->headers->get('roles')) {
                $roles = explode(',', $this->requestStack->getCurrentRequest()?->headers->get('roles'));
            }
            $roles[] = 'ROLE_SERVICE_' . strtoupper($this->zone->getCurrent());
            $apiUser->setRoles($roles);
            return $apiUser;
        });
        return new SelfValidatingPassport($userBadge);
    }

    /**
     * Called when authentication executed and was successful!
     *
     * This should return the Response sent back to the user, like a
     * RedirectResponse to the last page they visited.
     *
     * If you return null, the current request will continue, and the user
     * will be authenticated. This makes sense, for example, with an API.
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    /**
     * Called when authentication executed, but failed (e.g. wrong username password).
     *
     * This should return the Response sent back to the user, like a
     * RedirectResponse to the login page or a 403 response.
     *
     * If you return null, the request will continue, but the user will
     * not be authenticated. This is probably not what you want to do.
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {

        $data = [
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData())
        ];
        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }
}
