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

namespace Scheb\TwoFactorBundle\Security\Authentication\Token;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

interface TwoFactorTokenFactoryInterface
{
    /**
     * Create a new TwoFactorToken.
     *
     * @param string|null $credentials        The two-factor authentication code or null
     * @param string      $providerKey        The firewall name
     * @param string[]    $twoFactorProviders The two-factor provider aliases, which are currently available
     */
    public function create(TokenInterface $authenticatedToken, ?string $credentials, string $providerKey, array $twoFactorProviders): TwoFactorTokenInterface;
}
