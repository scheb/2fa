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

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Provider;

use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;

interface TwoFactorProviderInterface
{
    /**
     * Return true when two-factor authentication process should be started.
     */
    public function beginAuthentication(AuthenticationContextInterface $context): bool;

    /**
     * Do all steps necessary to prepare authentication, e.g. generate & send a code.
     *
     * @param mixed $user
     */
    public function prepareAuthentication($user): void;

    /**
     * Validate the two-factor authentication code.
     *
     * @param mixed $user
     */
    public function validateAuthenticationCode($user, string $authenticationCode): bool;

    /**
     * Return the form renderer for two-factor authentication.
     */
    public function getFormRenderer(): TwoFactorFormRendererInterface;
}
