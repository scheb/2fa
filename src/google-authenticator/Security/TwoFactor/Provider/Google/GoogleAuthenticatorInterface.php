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

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google;

use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface;

interface GoogleAuthenticatorInterface
{
    /**
     * Validates the code, which was entered by the user.
     */
    public function checkCode(TwoFactorInterface $user, string $code): bool;

    /**
     * Generate the content for a QR-Code to be scanned by Google Authenticator.
     */
    public function getQRContent(TwoFactorInterface $user): string;

    /**
     * Generate a new secret for Google Authenticator.
     */
    public function generateSecret(): string;
}
