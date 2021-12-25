<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Backup;

use Scheb\TwoFactorBundle\Model\BackupCodeInterface;
use Scheb\TwoFactorBundle\Model\PersisterInterface;

/**
 * @final
 */
class BackupCodeManager implements BackupCodeManagerInterface
{
    public function __construct(private PersisterInterface $persister)
    {
    }

    public function isBackupCode(object $user, string $code): bool
    {
        if ($user instanceof BackupCodeInterface) {
            return $user->isBackupCode($code);
        }

        return false;
    }

    public function invalidateBackupCode(object $user, string $code): void
    {
        if (!($user instanceof BackupCodeInterface)) {
            return;
        }

        $user->invalidateBackupCode($code);
        $this->persister->persist($user);
    }
}
