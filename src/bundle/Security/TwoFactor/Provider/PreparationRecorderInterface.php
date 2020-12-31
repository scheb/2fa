<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Provider;

/**
 * @internal
 */
interface PreparationRecorderInterface
{
    /**
     * Check if a two-factor provider has completed preparation. The provider's alias is passed as the argument.
     */
    public function isTwoFactorProviderPrepared(string $firewallName, string $providerName): bool;

    /**
     * Remember when a two-factor provider has completed preparation. The provider's alias is passed as the argument.
     */
    public function setTwoFactorProviderPrepared(string $firewallName, string $providerName): void;
}
