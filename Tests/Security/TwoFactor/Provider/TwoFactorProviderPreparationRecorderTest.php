<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Provider;

use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderPreparationRecorder;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class TwoFactorProviderPreparationRecorderTest extends TestCase
{
    private const FIREWALL_NAME = 'firewallName';
    private const CURRENT_PROVIDER_NAME = 'currentProviderName';

    /**
     * @var MockObject|SessionInterface
     */
    private $session;

    /**
     * @var TwoFactorProviderPreparationRecorder
     */
    private $recorder;

    protected function setUp(): void
    {
        $this->session = $this->createMock(SessionInterface::class);
        $this->recorder = new TwoFactorProviderPreparationRecorder($this->session);
    }

    /**
     * @test
     */
    public function isProviderPrepared_providerIsNotPrepared_returnFalse()
    {
        $this->session
            ->expects($this->once())
            ->method('get')
            ->with('2fa_called_providers')
            ->willReturn(['otherFirewallName' => [self::CURRENT_PROVIDER_NAME]]);

        $returnValue = $this->recorder->isProviderPrepared(self::FIREWALL_NAME, self::CURRENT_PROVIDER_NAME);
        $this->assertFalse($returnValue);
    }

    /**
     * @test
     */
    public function isProviderPrepared_providerIsPrepared_returnTrue()
    {
        $this->session
            ->expects($this->once())
            ->method('get')
            ->with('2fa_called_providers')
            ->willReturn([self::FIREWALL_NAME => [self::CURRENT_PROVIDER_NAME]]);

        $returnValue = $this->recorder->isProviderPrepared(self::FIREWALL_NAME, self::CURRENT_PROVIDER_NAME);
        $this->assertTrue($returnValue);
    }

    /**
     * @test
     */
    public function onTwoFactorAuthenticationRequest_authenticationRequired_alreadyPrepared_doNothing(): void
    {
        $this->session
            ->expects($this->once())
            ->method('get')
            ->with('2fa_called_providers')
            ->willReturn(['otherFirewallName' => [self::CURRENT_PROVIDER_NAME]]);

        $this->session
            ->expects($this->once())
            ->method('set')
            ->with(
                '2fa_called_providers',
                [
                    'otherFirewallName' => [self::CURRENT_PROVIDER_NAME],
                    self::FIREWALL_NAME => [self::CURRENT_PROVIDER_NAME],
                ]
            );

        $this->recorder->recordProviderIsPrepared(self::FIREWALL_NAME, self::CURRENT_PROVIDER_NAME);
    }

    /**
     * @test
     */
    public function saveSession_sessionIsStarted_save(): void
    {
        $this->session
            ->expects($this->any())
            ->method('isStarted')
            ->willReturn(true);

        $this->session
            ->expects($this->once())
            ->method('save');

        $this->recorder->saveSession();
    }

    /**
     * @test
     */
    public function saveSession_sessionNotStarted_doNotSave(): void
    {
        $this->session
            ->expects($this->any())
            ->method('isStarted')
            ->willReturn(false);

        $this->session
            ->expects($this->never())
            ->method('save');

        $this->recorder->saveSession();
    }
}
