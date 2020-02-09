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

namespace Scheb\TwoFactorBundle\Mailer;

use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;

interface AuthCodeMailerInterface
{
    /**
     * Send the auth code to the user via email.
     */
    public function sendAuthCode(TwoFactorInterface $user): void;
}
