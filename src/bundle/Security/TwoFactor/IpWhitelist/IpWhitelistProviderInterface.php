<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\IpWhitelist;

use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;

interface IpWhitelistProviderInterface
{
    /**
     * Return a list of whitelisted IP addresses, which don't need to perform two-factor authentication.
     *
     * @return string[]
     */
    public function getWhitelistedIps(AuthenticationContextInterface $context): array;
}
