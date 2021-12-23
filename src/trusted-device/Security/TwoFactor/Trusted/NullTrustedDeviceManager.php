<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Trusted;

use Symfony\Component\HttpFoundation\Request;

/**
 * @final
 */
class NullTrustedDeviceManager implements TrustedDeviceManagerInterface
{
    public function canSetTrustedDevice(object $user, Request $request, string $firewallName): bool
    {
        return false;
    }

    public function addTrustedDevice(object $user, string $firewallName): void
    {
    }

    public function isTrustedDevice(object $user, string $firewallName): bool
    {
        return false;
    }
}
