<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Authentication;

use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

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

    private function isTwoFactorToken(?TokenInterface $token): bool
    {
        return $token instanceof TwoFactorTokenInterface;
    }
}
