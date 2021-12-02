<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Trusted;

use Symfony\Component\HttpFoundation\Request;

/**
 * @final
 */
class NullTrustedDeviceManager implements TrustedDeviceManagerInterface
{
    public function canSetTrustedDevice(mixed $user, Request $request, string $firewallName): bool
    {
        return false;
    }

    public function addTrustedDevice(mixed $user, string $firewallName): void
    {
    }

    public function isTrustedDevice(mixed $user, string $firewallName): bool
    {
        return false;
    }
}
