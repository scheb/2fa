<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Provider;

use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorToken;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvent;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderPreparationListener;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderRegistry;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class TwoFactorProviderPreparationListenerTest extends TestCase
{
    const FIREWALL_NAME = 'firewallName';
    const CURRENT_PROVIDER_NAME = 'currentProviderName';

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
    private $session;

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

        $this->session = $this->createMock(SessionInterface::class);

        $this->providerRegistry = $this->createMock(TwoFactorProviderRegistry::class);
        $this->listener = new TwoFactorProviderPreparationListener($this->providerRegistry, $this->session);
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

        $this->session
            ->expects($this->once())
            ->method('get')
            ->with('2fa_called_providers')
            ->willReturn([]);

        $this->providerRegistry
            ->expects($this->once())
            ->method('getProvider')
            ->with(self::CURRENT_PROVIDER_NAME)
            ->willReturn($twoFactorProvider);

        $this->session
            ->expects($this->once())
            ->method('set')
            ->with(
                '2fa_called_providers',
                [self::CURRENT_PROVIDER_NAME]
            );

        $twoFactorProvider
            ->expects($this->once())
            ->method('prepareAuthentication')
            ->with($this->identicalTo($this->user));

        $this->listener->onTwoFactorAuthenticationRequest($event);
        $this->listener->onKernelResponse($this->createMock(FilterResponseEvent::class));
    }

    /**
     * @test
     */
    public function onTwoFactorAuthenticationRequest_authenticationRequired_alreadyPrepared_doNothing(): void
    {
        $event = $this->createTwoFactorAuthenticationEvent();
        $twoFactorProvider = $this->createMock(TwoFactorProviderInterface::class);

        $this->session
            ->expects($this->once())
            ->method('get')
            ->with('2fa_called_providers')
            ->willReturn([self::CURRENT_PROVIDER_NAME]);

        $this->providerRegistry
            ->expects($this->never())
            ->method('getProvider');

        $this->listener->onTwoFactorAuthenticationRequest($event);
        $this->listener->onKernelResponse($this->createMock(FilterResponseEvent::class));
    }
}
