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

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Backup;

class NullBackupCodeManager implements BackupCodeManagerInterface
{
    public function isBackupCode($user, string $code): bool
    {
        return false;
    }

    public function invalidateBackupCode($user, string $code): void
    {
    }
}
