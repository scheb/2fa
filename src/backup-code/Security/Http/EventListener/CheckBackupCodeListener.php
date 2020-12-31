<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Http\EventListener;

use Scheb\TwoFactorBundle\Security\TwoFactor\Backup\BackupCodeManagerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\PreparationRecorderInterface;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;

/**
 * @final
 */
class CheckBackupCodeListener extends AbstractCheckCodeListener
{
    // Must be called before CheckTwoFactorCodeListener, because CheckTwoFactorCodeListener will throw an exception
    // when the code is wrong.
    public const LISTENER_PRIORITY = CheckTwoFactorCodeListener::LISTENER_PRIORITY + 16;

    /**
     * @var BackupCodeManagerInterface
     */
    private $backupCodeManager;

    public function __construct(
        PreparationRecorderInterface $preparationRecorder,
        BackupCodeManagerInterface $backupCodeManager
    ) {
        parent::__construct($preparationRecorder);
        $this->backupCodeManager = $backupCodeManager;
    }

    /**
     * @param object|string $user
     */
    protected function isValidCode(string $providerName, $user, string $code): bool
    {
        if ($this->backupCodeManager->isBackupCode($user, $code)) {
            $this->backupCodeManager->invalidateBackupCode($user, $code);

            return true;
        }

        return false;
    }

    public static function getSubscribedEvents(): array
    {
        return [CheckPassportEvent::class => ['checkPassport', self::LISTENER_PRIORITY]];
    }
}
