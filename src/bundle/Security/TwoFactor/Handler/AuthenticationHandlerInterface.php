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

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Handler;

use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

interface AuthenticationHandlerInterface
{
    /**
     * Begin the two-factor authentication process.
     */
    public function beginTwoFactorAuthentication(AuthenticationContextInterface $context): TokenInterface;
}
