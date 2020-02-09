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
            throw new \InvalidArgumentException(sprintf('Firewall "%s" has no two-factor config.', $firewallName));
        }

        return $this->firewallConfigs[$firewallName];
    }
}
