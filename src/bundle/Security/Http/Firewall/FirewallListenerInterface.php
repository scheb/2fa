<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Http\Firewall;

use Symfony\Component\Security\Http\Firewall\FirewallListenerInterface as BaseFirewallListenerInterface;

if (interface_exists(BaseFirewallListenerInterface::class)) {
    // Compatibility for Symfony >= 5.2
    interface FirewallListenerInterface extends BaseFirewallListenerInterface
    {
    }
} else {
    // Compatibility for Symfony <= 5.1
    interface FirewallListenerInterface
    {
    }
}
