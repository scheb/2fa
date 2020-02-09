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

interface PreferredProviderInterface
{
    /**
     * Return the alias of the preferred two-factor provider (if chosen by the user).
     */
    public function getPreferredTwoFactorProvider(): ?string;
}
