<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\Http\EventListener;

use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\Http\EventListener\CheckBackupCodeListener;
use Scheb\TwoFactorBundle\Security\TwoFactor\Backup\BackupCodeManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @property CheckBackupCodeListener $listener
 */
class CheckBackupCodeListenerTest extends AbstractCheckCodeListenerTestSetup
{
    private MockObject|BackupCodeManagerInterface $backupCodeManager;
    private MockObject|TokenStorageInterface $tokenStorage;
    private MockObject|RequestStack $requestStack;
    private MockObject|EventDispatcherInterface $eventDispatcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->backupCodeManager = $this->createMock(BackupCodeManagerInterface::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->eventDispatecher = $this->createMock(EventDispatcherInterface::class);
        $this->listener = new CheckBackupCodeListener($this->preparationRecorder, $this->backupCodeManager, $this->tokenStorage, $this->requestStack, $this->eventDispatecher);
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
}
