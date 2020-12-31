<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Backup;

/**
 * @final
 */
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
