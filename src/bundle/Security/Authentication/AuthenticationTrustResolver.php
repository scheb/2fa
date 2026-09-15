<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Authentication;

use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use function method_exists;

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

    /**
     * Declared on the interface since Symfony 8.2, where not implementing it is deprecated.
     */
    public function isAuthenticatedRecently(TokenInterface|null $token = null): bool
    {
        return $this->isAuthenticatedRecentlyEnough(__FUNCTION__, $token);
    }

    /**
     * Declared on the interface since Symfony 8.2, where not implementing it is deprecated.
     */
    public function isAuthenticatedVeryRecently(TokenInterface|null $token = null): bool
    {
        return $this->isAuthenticatedRecentlyEnough(__FUNCTION__, $token);
    }

    private function isAuthenticatedRecentlyEnough(string $method, TokenInterface|null $token): bool
    {
        // A pending two-factor authentication is no proof of anything yet
        if ($this->isTwoFactorToken($token)) {
            return false;
        }

        // The decorated resolver only has the method on Symfony 8.2+
        if (!method_exists($this->decoratedTrustResolver, $method)) {
            return false;
        }

        /** @psalm-suppress MixedAssignment, MixedMethodCall */
        $result = $this->decoratedTrustResolver->$method($token);

        return true === $result;
    }

    private function isTwoFactorToken(TokenInterface|null $token): bool
    {
        return $token instanceof TwoFactorTokenInterface;
    }
}
