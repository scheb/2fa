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

interface BackupCodeInterface
{
    /**
     * Check if it is a valid backup code.
     */
    public function isBackupCode(string $code): bool;

    /**
     * Invalidate a backup code.
     */
    public function invalidateBackupCode(string $code): void;
}
