<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Provider;

use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorToken;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvent;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderPreparationListener;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderPreparationRecorder;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderRegistry;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class TwoFactorProviderPreparationListenerTest extends TestCase
{
    private const FIREWALL_NAME = 'firewallName';
    private const CURRENT_PROVIDER_NAME = 'currentProviderName';

    /**
     * @var MockObject|TwoFactorProviderRegistry
     */
    private $providerRegistry;

    /**
     * @var MockObject|Request
     */
    private $request;

    /**
     * @var MockObject|SessionInterface
     */
    private $preparationRecorder;

    /**
     * @var MockObject|TwoFactorToken
     */
    private $token;

    /**
     * @var
     */
    private $user;

    /**
     * @var TwoFactorProviderPreparationListener
     */
    private $listener;

    protected function setUp(): void
    {
        $this->request = $this->createMock(Request::class);
        $this->user = new \stdClass();
        $this->token = $this->createMock(TwoFactorToken::class);
        $this->token
            ->expects($this->any())
            ->method('getProviderKey')
            ->willReturn(self::FIREWALL_NAME);
        $this->token
            ->expects($this->any())
            ->method('getCurrentTwoFactorProvider')
            ->willReturn(self::CURRENT_PROVIDER_NAME);
        $this->token
            ->expects($this->any())
            ->method('getUser')
            ->willReturn($this->user);

        $this->preparationRecorder = $this->createMock(TwoFactorProviderPreparationRecorder::class);

        $this->providerRegistry = $this->createMock(TwoFactorProviderRegistry::class);
        $this->listener = new TwoFactorProviderPreparationListener(
            $this->providerRegistry,
            $this->preparationRecorder,
            $this->createMock(LoggerInterface::class)
        );
    }

    private function createTwoFactorAuthenticationEvent(): TwoFactorAuthenticationEvent
    {
        return new TwoFactorAuthenticationEvent($this->request, $this->token);
    }

    /**
     * @test
     */
    public function onTwoFactorAuthenticationRequest_authenticationRequired_prepareTwoFactorProvider(): void
    {
        $event = $this->createTwoFactorAuthenticationEvent();
        $twoFactorProvider = $this->createMock(TwoFactorProviderInterface::class);

        $this->preparationRecorder
            ->expects($this->once())
            ->method('isProviderPrepared')
            ->with(self::FIREWALL_NAME, self::CURRENT_PROVIDER_NAME)
            ->willReturn(false);

        $this->providerRegistry
            ->expects($this->once())
            ->method('getProvider')
            ->with(self::CURRENT_PROVIDER_NAME)
            ->willReturn($twoFactorProvider);

        $this->preparationRecorder
            ->expects($this->once())
            ->method('recordProviderIsPrepared')
            ->with(self::FIREWALL_NAME, self::CURRENT_PROVIDER_NAME);

        $twoFactorProvider
            ->expects($this->once())
            ->method('prepareAuthentication')
            ->with($this->identicalTo($this->user));

        $this->listener->onTwoFactorAuthenticationRequiredEvent($event);
        $this->listener->onKernelTerminate();
    }

    /**
     * @test
     */
    public function onTwoFactorAuthenticationRequest_authenticationRequired_alreadyPrepared_doNothing(): void
    {
        $event = $this->createTwoFactorAuthenticationEvent();

        $this->preparationRecorder
            ->expects($this->once())
            ->method('isProviderPrepared')
            ->with(self::FIREWALL_NAME, self::CURRENT_PROVIDER_NAME)
            ->willReturn(true);

        $this->preparationRecorder
            ->expects($this->never())
            ->method('recordProviderIsPrepared');

        $this->providerRegistry
            ->expects($this->never())
            ->method('getProvider');

        $this->listener->onTwoFactorAuthenticationRequiredEvent($event);
        $this->listener->onKernelTerminate();
    }
}
