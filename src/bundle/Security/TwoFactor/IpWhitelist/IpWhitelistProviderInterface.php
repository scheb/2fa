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

namespace Scheb\TwoFactorBundle\Security\TwoFactor\IpWhitelist;

use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;

interface IpWhitelistProviderInterface
{
    /**
     * Return a list of whitelisted IP addresses, which don't need to perform two-factor authentication.
     *
     * @return string[]
     */
    public function getWhitelistedIps(AuthenticationContextInterface $context): array;
}
