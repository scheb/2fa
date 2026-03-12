<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Provider;

interface TwoFactorProviderRegistryInterface
{
    /**
     * @return iterable<string,TwoFactorProviderInterface>
     */
    public function getAllProviders(): iterable;

    public function getProvider(string $providerName): TwoFactorProviderInterface;
}
