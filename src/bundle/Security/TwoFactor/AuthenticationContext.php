<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class AuthenticationContext implements AuthenticationContextInterface
{
    public function __construct(private Request $request, private TokenInterface $token, private string $firewallName)
    {
    }

    public function getToken(): TokenInterface
    {
        return $this->token;
    }

    public function getUser(): ?UserInterface
    {
        return $this->token->getUser();
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getSession(): SessionInterface
    {
        return $this->request->getSession();
    }

    public function getFirewallName(): string
    {
        return $this->firewallName;
    }
}
