<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

interface AuthenticationContextInterface
{
    /**
     * Return the security token.
     */
    public function getToken(): TokenInterface;

    /**
     * Return the user object.
     *
     * @return mixed
     */
    public function getUser();

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
