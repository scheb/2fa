<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Condition;

use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;

interface TwoFactorConditionRegistryInterface
{
    public function shouldPerformTwoFactorAuthentication(AuthenticationContextInterface $context): bool;
}
