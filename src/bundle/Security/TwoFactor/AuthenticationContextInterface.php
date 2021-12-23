<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

interface AuthenticationContextInterface
{
    /**
     * Return the security token.
     */
    public function getToken(): TokenInterface;

    /**
     * Return the passport used in the initial authentication process.
     */
    public function getPassport(): Passport;

    /**
     * Return the user object.
     */
    public function getUser(): UserInterface;

    /**
     * Return the request.
     */
    public function getRequest(): Request;

    /**
     * Return the session.
     */
    public function getSession(): SessionInterface;

    /**
     * Return the firewall name.
     */
    public function getFirewallName(): string;
}
