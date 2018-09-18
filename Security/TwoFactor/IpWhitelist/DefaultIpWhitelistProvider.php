<?php

namespace Scheb\TwoFactorBundle\Security\TwoFactor\IpWhitelist;

class DefaultIpWhitelistProvider implements IpWhitelistProviderInterface
{
    /**
     * @var string[]
     */
    private $ipWhitelist;

    public function __construct(array $ipWhitelist)
    {
        $this->ipWhitelist = $ipWhitelist;
    }

    public function getWhitelistedIps(): array
    {
        return $this->ipWhitelist;
    }
}
