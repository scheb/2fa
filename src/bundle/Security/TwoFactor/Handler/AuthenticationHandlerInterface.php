<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Handler;

use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @internal
 */
interface AuthenticationHandlerInterface
{
    /**
     * Begin the two-factor authentication process.
     */
    public function beginTwoFactorAuthentication(AuthenticationContextInterface $context): TokenInterface;
}
