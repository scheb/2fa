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

namespace Scheb\TwoFactorBundle\Model;

interface TrustedDeviceInterface
{
    /**
     * Return version for the trusted token. Increase version to invalidate all trusted token of the user.
     */
    public function getTrustedTokenVersion(): int;
}
