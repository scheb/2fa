<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Authentication;

use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @final
 */
class AuthenticationTrustResolver implements AuthenticationTrustResolverInterface
{
    public function __construct(private readonly AuthenticationTrustResolverInterface $decoratedTrustResolver)
    {
    }

    public function isRememberMe(TokenInterface|null $token = null): bool
    {
        return $this->decoratedTrustResolver->isRememberMe($token);
    }

    public function isFullFledged(TokenInterface|null $token = null): bool
    {
        return !$this->isTwoFactorToken($token) && $this->decoratedTrustResolver->isFullFledged($token);
    }

    public function isAuthenticated(TokenInterface|null $token = null): bool
    {
        return $this->decoratedTrustResolver->isAuthenticated($token);
    }

    private function isTwoFactorToken(TokenInterface|null $token): bool
    {
        return $token instanceof TwoFactorTokenInterface;
    }
}
