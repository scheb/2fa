<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Trusted;

use Symfony\Component\HttpFoundation\Request;

interface TrustedDeviceManagerInterface
{
    /**
     * Check if it's allowed to set a trusted device token.
     *
     * @param mixed $user
     */
    public function canSetTrustedDevice($user, Request $request, string $firewallName): bool;

    /**
     * Add a trusted device token for a user.
     *
     * @param mixed $user
     */
    public function addTrustedDevice($user, string $firewallName): void;

    /**
     * Validate a device device token for a user.
     *
     * @param mixed $user
     */
    public function isTrustedDevice($user, string $firewallName): bool;
}
