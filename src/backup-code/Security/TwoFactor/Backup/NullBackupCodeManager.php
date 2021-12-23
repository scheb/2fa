<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Backup;

/**
 * @final
 */
class NullBackupCodeManager implements BackupCodeManagerInterface
{
    public function isBackupCode(object $user, string $code): bool
    {
        return false;
    }

    public function invalidateBackupCode(object $user, string $code): void
    {
    }
}
