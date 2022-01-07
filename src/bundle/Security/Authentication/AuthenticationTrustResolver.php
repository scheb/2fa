<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Authentication;

use RuntimeException;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use function method_exists;

/**
 * @final
 */
class AuthenticationTrustResolver implements AuthenticationTrustResolverInterface
{
    public function __construct(private AuthenticationTrustResolverInterface $decoratedTrustResolver)
    {
    }

    /**
     * Compatibility with Symfony < 6.0.
     *
     * @deprecated since Symfony 5.4, use !isAuthenticated() instead
     */
    public function isAnonymous(?TokenInterface $token = null): bool
    {
        if (!method_exists($this->decoratedTrustResolver, 'isAnonymous')) {
            throw new RuntimeException('Method "isAnonymous" was not declared on the decorated AuthenticationTrustResolverInterface');
        }

        return $this->decoratedTrustResolver->isAnonymous($token);
    }

    public function isRememberMe(?TokenInterface $token = null): bool
    {
        return $this->decoratedTrustResolver->isRememberMe($token);
    }

    public function isFullFledged(?TokenInterface $token = null): bool
    {
        return !$this->isTwoFactorToken($token) && $this->decoratedTrustResolver->isFullFledged($token);
    }

    /**
     * Compatibility for Symfony >= 5.4.
     */
    public function isAuthenticated(?TokenInterface $token = null): bool
    {
        // The "isAuthenticated" method must be declared in Symfony >= 6.0
        if (method_exists($this->decoratedTrustResolver, 'isAuthenticated')) {
            return $this->decoratedTrustResolver->isAuthenticated($token);
        }

        // Fallback for Symfony 5.4, when "isAuthenticated" is not declared, use the deprecated "isAnonymous" method
        if (method_exists($this->decoratedTrustResolver, 'isAnonymous')) {
            return !$this->decoratedTrustResolver->isAnonymous($token);
        }

        // This should never happen on Symfony 5.4 or 6.x versions, either "isAuthenticated" or "isAnonymous" must be declared
        throw new RuntimeException('Neither method "isAuthenticated" nor "isAnonymous" was declared on the decorated AuthenticationTrustResolverInterface');
    }

    private function isTwoFactorToken(?TokenInterface $token): bool
    {
        return $token instanceof TwoFactorTokenInterface;
    }
}
