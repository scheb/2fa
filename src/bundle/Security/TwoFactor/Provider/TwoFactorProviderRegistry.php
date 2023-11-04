<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Provider;

use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Exception\UnknownTwoFactorProviderException;
use function sprintf;

/**
 * @final
 */
class TwoFactorProviderRegistry
{
    /**
     * @param iterable<string,TwoFactorProviderInterface> $providers
     */
    public function __construct(private readonly iterable $providers)
    {
    }

    /**
     * @return iterable<string,TwoFactorProviderInterface>
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
