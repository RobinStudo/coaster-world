<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class AppLoginAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    const LOGIN_ROUTE = 'security_login';
    private RouterInterface $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function supports(Request $request): ?bool
    {
        if ($request->attributes->get('_route') !== self::LOGIN_ROUTE) {
            return false;
        }

        if ($request->getMethod() !== 'POST') {
            return false;
        }

        if (!$request->request->has('email') || !$request->request->has('password')) {
            return false;
        }

        return true;
    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('email');
        $password = $request->request->get('password');
        $csrfToken = $request->request->get('token');

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('authentication', $csrfToken),
                new RememberMeBadge()
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $flashBag = $request->getSession()->getFlashBag();
        $flashBag->add('success', 'Vous êtes bien connecté');

        $url = $this->router->generate('main_home');
        return new RedirectResponse($url);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);
        $request->getSession()->set(Security::LAST_USERNAME, $request->request->get('email'));

        return $this->redirectToLogin();
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return $this->redirectToLogin();
    }

    private function redirectToLogin(): Response
    {
        $url = $this->router->generate(self::LOGIN_ROUTE);
        return new RedirectResponse($url);
    }
}
