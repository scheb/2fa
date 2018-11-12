<?php

namespace Scheb\TwoFactorBundle\Security\TwoFactor\IpWhitelist;


use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;

interface IpWhitelistProviderInterface
{
    /**
     * Return a list of whitelisted IP addresses, which don't need to perform two-factor authentication.
     *
     * @param AuthenticationContextInterface $context
     *
     * @return string[]
     */
    public function getWhitelistedIps(AuthenticationContextInterface $context): array;
}
