<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor;

class TwoFactorFirewallContext
{
    private $firewallConfigs = [];

    public function __construct(array $firewallConfigs)
    {
        $this->firewallConfigs = $firewallConfigs;
    }

    public function getFirewallConfig(string $firewallName): TwoFactorFirewallConfig
    {
        if (!isset($this->firewallConfigs[$firewallName])) {
            throw new \InvalidArgumentException('Firewall "'.$firewallName.'" has no two-factor config.');
        }

        return $this->firewallConfigs[$firewallName];
    }
}
