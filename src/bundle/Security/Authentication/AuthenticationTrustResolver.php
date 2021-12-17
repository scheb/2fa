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
    /**
     * @var AuthenticationTrustResolverInterface
     */
    private $decoratedTrustResolver;

    public function __construct(AuthenticationTrustResolverInterface $decoratedTrustResolver)
    {
        $this->decoratedTrustResolver = $decoratedTrustResolver;
    }

    // Compatibility for Symfony <= 5.4
    public function isAnonymous(TokenInterface $token = null): bool
    {
        return $this->decoratedTrustResolver->isAnonymous($token);
    }

    public function isRememberMe(TokenInterface $token = null): bool
    {
        return $this->decoratedTrustResolver->isRememberMe($token);
    }

    public function isFullFledged(TokenInterface $token = null): bool
    {
        return !$this->isTwoFactorToken($token) && $this->decoratedTrustResolver->isFullFledged($token);
    }

    // Compatibility for Symfony >= 5.4
    public function isAuthenticated(TokenInterface $token = null): bool
    {
        // When isAuthenticated method is implemented
        if (method_exists($this->decoratedTrustResolver, 'isAuthenticated')) {
            return $this->decoratedTrustResolver->isAuthenticated($token);
        }

        // Fallback when it's not implemented
        return !$this->decoratedTrustResolver->isAnonymous($token);
    }

    private function isTwoFactorToken(?TokenInterface $token): bool
    {
        return $token instanceof TwoFactorTokenInterface;
    }
}
