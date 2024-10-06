<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Http\EventListener;

use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Backup\BackupCodeManagerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvent;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvents;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\PreparationRecorderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @final
 */
class CheckBackupCodeListener extends AbstractCheckCodeListener
{
    // Must be called before CheckTwoFactorCodeListener, because CheckTwoFactorCodeListener will throw an exception
    // when the code is wrong.
    public const LISTENER_PRIORITY = CheckTwoFactorCodeListener::LISTENER_PRIORITY + 16;

    public function __construct(
        PreparationRecorderInterface $preparationRecorder,
        private readonly BackupCodeManagerInterface $backupCodeManager,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly RequestStack $requestStack,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
        parent::__construct($preparationRecorder);
    }

    protected function isValidCode(string $providerName, object $user, string $code): bool
    {
        if ($this->backupCodeManager->isBackupCode($user, $code)) {
            $this->backupCodeManager->invalidateBackupCode($user, $code);
            $token = $this->tokenStorage->getToken();
            if (!($token instanceof TwoFactorTokenInterface)) {
                return false;
            }

            $request = $this->requestStack->getCurrentRequest();
            if ($request) {
                $event = new TwoFactorAuthenticationEvent($request, $token);
                $this->eventDispatcher->dispatch($event, TwoFactorAuthenticationEvents::BACKUP_CODE_USED);
            }

            return true;
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [CheckPassportEvent::class => ['checkPassport', self::LISTENER_PRIORITY]];
    }
}
