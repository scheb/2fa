<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Condition;

use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;

interface TwoFactorConditionInterface
{
    /**
     * Check if two-factor authentication should be performed for the user under current conditions.
     */
    public function shouldPerformTwoFactorAuthentication(AuthenticationContextInterface $context): bool;
}
