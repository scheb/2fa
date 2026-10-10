<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\Authentication;

use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * A trust resolver of Symfony 8.2, which declares both methods on its interface.
 */
class RecencyAwareTrustResolver implements AuthenticationTrustResolverInterface
{
    public bool $result = false;
    public string|null $calledMethod = null;

    public function isAuthenticated(TokenInterface|null $token = null): bool
    {
        return false;
    }

    public function isRememberMe(TokenInterface|null $token = null): bool
    {
        return false;
    }

    public function isFullFledged(TokenInterface|null $token = null): bool
    {
        return false;
    }

    public function isAuthenticatedRecently(TokenInterface|null $token = null): bool
    {
        $this->calledMethod = __FUNCTION__;

        return $this->result;
    }

    public function isAuthenticatedVeryRecently(TokenInterface|null $token = null): bool
    {
        $this->calledMethod = __FUNCTION__;

        return $this->result;
    }
}
