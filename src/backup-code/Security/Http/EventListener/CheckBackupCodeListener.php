<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Http\EventListener;

use Scheb\TwoFactorBundle\Security\TwoFactor\Backup\BackupCodeManagerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\BackupCodeEvents;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorCodeEvent;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\PreparationRecorderInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @final
 */
class CheckBackupCodeListener extends AbstractCheckCodeListener
{
    // Must be called before CheckTwoFactorCodeListener, because CheckTwoFactorCodeListener will throw an exception
    // when the code is wrong.
    public const int LISTENER_PRIORITY = CheckTwoFactorCodeListener::LISTENER_PRIORITY + 16;

    public function __construct(
        PreparationRecorderInterface $preparationRecorder,
        private readonly BackupCodeManagerInterface $backupCodeManager,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
        parent::__construct($preparationRecorder);
    }

    protected function isValidCode(Passport $passport, string $providerName, object $user, string $code): bool
    {
        $event = new TwoFactorCodeEvent($user, $code);
        $this->eventDispatcher->dispatch($event, BackupCodeEvents::CHECK);

        // Symfony 8.2 records on the token which authentication methods were proven, from that badge
        if (class_exists(AuthenticationMethodBadge::class)) {
            /** @psalm-suppress UndefinedClass, InvalidArgument, MixedMethodCall, MixedArgument */
            $passport->addBadge(new AuthenticationMethodBadge('otp'));
        }

        if ($this->backupCodeManager->isBackupCode($user, $code)) {
            $this->eventDispatcher->dispatch($event, BackupCodeEvents::VALID);
            $this->backupCodeManager->invalidateBackupCode($user, $code);

            return true;
        }

        $this->eventDispatcher->dispatch($event, BackupCodeEvents::INVALID);

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
