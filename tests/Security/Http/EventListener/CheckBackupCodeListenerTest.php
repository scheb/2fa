<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\Http\EventListener;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\Http\EventListener\CheckBackupCodeListener;
use Scheb\TwoFactorBundle\Security\TwoFactor\Backup\BackupCodeManagerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\BackupCodeEvents;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorCodeEvent;
use Scheb\TwoFactorBundle\Tests\EventDispatcherTestHelper;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @property CheckBackupCodeListener $listener
 */
class CheckBackupCodeListenerTest extends AbstractCheckCodeListenerTestSetup
{
    use EventDispatcherTestHelper;

    private MockObject|BackupCodeManagerInterface $backupCodeManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->backupCodeManager = $this->createMock(BackupCodeManagerInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->listener = new CheckBackupCodeListener($this->preparationRecorder, $this->backupCodeManager, $this->eventDispatcher);
    }

    protected function expectDoNothing(): void
    {
        $this->backupCodeManager
            ->expects($this->never())
            ->method($this->anything());
    }

    #[Test]
    public function checkPassport_validBackupCode_invalidateAndResolveCredentials(): void
    {
        $this->stubAllPreconditionsFulfilled();

        $this->backupCodeManager
            ->expects($this->once())
            ->method('isBackupCode')
            ->with($this->user, self::CODE)
            ->willReturn(true);

        $this->backupCodeManager
            ->expects($this->once())
            ->method('invalidateBackupCode')
            ->with($this->user, self::CODE);

        $this->expectMarkCredentialsResolved();

        $this->listener->checkPassport($this->checkPassportEvent);
    }

    /**
     * @test
     */
    public function checkPassport_validBackupCode_dispatchCheckAndInvalidateEvent(): void
    {
        $this->stubAllPreconditionsFulfilled();

        $this->backupCodeManager
            ->expects($this->any())
            ->method('isBackupCode')
            ->willReturn(true);

        $event = new TwoFactorCodeEvent($this->user, self::CODE);
        $this->expectDispatchConsecutiveEvents([
            [$event, BackupCodeEvents::CHECK],
            [$event, BackupCodeEvents::VALID],
        ]);

        $this->listener->checkPassport($this->checkPassportEvent);
    }

    #[Test]
    public function checkPassport_invalidBackupCode_unresolvedCredentials(): void
    {
        $this->stubAllPreconditionsFulfilled();

        $this->backupCodeManager
            ->expects($this->once())
            ->method('isBackupCode')
            ->with($this->user, self::CODE)
            ->willReturn(false);

        $this->backupCodeManager
            ->expects($this->never())
            ->method('invalidateBackupCode');

        $this->expectCredentialsUnresolved();

        $this->listener->checkPassport($this->checkPassportEvent);
    }

    /**
     * @test
     */
    public function checkPassport_invalidBackupCode_dispatchCheckEvent(): void
    {
        $this->stubAllPreconditionsFulfilled();

        $this->backupCodeManager
            ->expects($this->any())
            ->method('isBackupCode')
            ->willReturn(false);

        $event = new TwoFactorCodeEvent($this->user, self::CODE);
        $this->expectDispatchConsecutiveEvents([
            [$event, BackupCodeEvents::CHECK],
            [$event, BackupCodeEvents::INVALID],
        ]);

        $this->listener->checkPassport($this->checkPassportEvent);
    }
}
