<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\DependencyInjection\Factory\Security;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\FirewallListenerFactoryInterface as BaseFirewallListenerFactoryInterface;

if (interface_exists(BaseFirewallListenerFactoryInterface::class)) {
    // Compatibility for Symfony >= 5.2
    interface FirewallListenerFactoryInterface extends BaseFirewallListenerFactoryInterface
    {
    }
} else {
    // Compatibility for Symfony <= 5.1
    interface FirewallListenerFactoryInterface
    {
    }
}
