<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\IpWhitelist;

use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;

/**
 * @final
 */
class DefaultIpWhitelistProvider implements IpWhitelistProviderInterface
{
    /**
     * @param string[] $ipWhitelist
     */
    public function __construct(private readonly array $ipWhitelist)
    {
    }

    /**
     * @return string[]
     */
    public function getWhitelistedIps(AuthenticationContextInterface $context): array
    {
        return $this->ipWhitelist;
    }
}
