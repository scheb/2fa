<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2020 Christian Scheb
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Provider;

use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Exception\UnknownTwoFactorProviderException;

class TwoFactorProviderRegistry
{
    /**
     * @var TwoFactorProviderInterface[]
     */
    private $providers;

    /**
     * @param TwoFactorProviderInterface[] $providers
     */
    public function __construct(array $providers)
    {
        $this->providers = $providers;
    }

    /**
     * @return iterable|TwoFactorProviderInterface[]
     */
    public function getAllProviders(): iterable
    {
        return $this->providers;
    }

    public function getProvider(string $providerName): TwoFactorProviderInterface
    {
        foreach ($this->providers as $name => $provider) {
            if ($name === $providerName) {
                return $provider;
            }
        }

        throw new UnknownTwoFactorProviderException(sprintf('Two-factor provider "%s" does not exist.', $providerName));
    }
}
