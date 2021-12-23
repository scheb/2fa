<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Trusted;

use Symfony\Component\HttpFoundation\Request;

interface TrustedDeviceManagerInterface
{
    /**
     * Check if it's allowed to set a trusted device token.
     */
    public function canSetTrustedDevice(object $user, Request $request, string $firewallName): bool;

    /**
     * Add a trusted device token for a user.
     */
    public function addTrustedDevice(object $user, string $firewallName): void;

    /**
     * Validate a device device token for a user.
     */
    public function isTrustedDevice(object $user, string $firewallName): bool;
}
