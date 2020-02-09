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

namespace Scheb\TwoFactorBundle\Model\Google;

interface TwoFactorInterface
{
    /**
     * Return true if the user should do two-factor authentication.
     */
    public function isGoogleAuthenticatorEnabled(): bool;

    /**
     * Return the user name.
     */
    public function getGoogleAuthenticatorUsername(): string;

    /**
     * Return the Google Authenticator secret
     * When an empty string is returned, the Google authentication is disabled.
     */
    public function getGoogleAuthenticatorSecret(): ?string;
}
