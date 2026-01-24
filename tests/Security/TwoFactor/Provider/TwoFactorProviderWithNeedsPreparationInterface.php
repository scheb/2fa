<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Provider;

use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderInterface;

/**
 * Used to mock providers with the needsPreparation method for forward compatibility testing.
 * In version 9, needsPreparation() will be required on TwoFactorProviderInterface.
 */
interface TwoFactorProviderWithNeedsPreparationInterface extends TwoFactorProviderInterface
{
    public function needsPreparation(): bool;
}
