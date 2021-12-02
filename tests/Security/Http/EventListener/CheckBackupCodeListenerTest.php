<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\Http\EventListener;

use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\Http\EventListener\CheckBackupCodeListener;
use Scheb\TwoFactorBundle\Security\TwoFactor\Backup\BackupCodeManagerInterface;

/**
 * @property CheckBackupCodeListener $listener
 */
class CheckBackupCodeListenerTest extends AbstractCheckCodeListenerTest
{
    private MockObject|BackupCodeManagerInterface $backupCodeManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->backupCodeManager = $this->createMock(BackupCodeManagerInterface::class);
        $this->listener = new CheckBackupCodeListener($this->preparationRecorder, $this->backupCodeManager);
    }

    protected function expectDoNothing(): void
    {
        $this->backupCodeManager
            ->expects($this->never())
            ->method($this->anything());
    }

    /**
     * @test
     */
    public function checkPassport_validBackupCode_invalidateAndResolveCredentials()
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
    public function checkPassport_invalidBackupCode_unresolvedCredentials()
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
}
