<?php

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Trusted;

interface TrustedDeviceManagerInterface
{
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
