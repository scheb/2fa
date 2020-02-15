<?php

declare(strict_types=1);

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
