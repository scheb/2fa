<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Backup;

interface BackupCodeManagerInterface
{
    /**
     * Check if the code is a valid backup code of the user.
     */
    public function isBackupCode(mixed $user, string $code): bool;

    /**
     * Invalidate a backup code from a user.
     */
    public function invalidateBackupCode(mixed $user, string $code): void;
}
