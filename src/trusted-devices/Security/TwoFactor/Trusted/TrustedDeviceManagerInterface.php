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
