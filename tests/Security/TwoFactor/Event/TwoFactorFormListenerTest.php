<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Event;

use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvent;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvents;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorFormListener;
use Scheb\TwoFactorBundle\Security\TwoFactor\TwoFactorFirewallConfig;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class TwoFactorFormListenerTest extends TestCase
{
    /**
     * @var MockObject|TwoFactorFirewallConfig
     */
    private $twoFactorFirewallConfig;

    /**
     * @var MockObject|TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var MockObject|EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var TwoFactorFormListener
     */
    private $listener;

    /**
     * @var MockObject|Request
     */
    private $request;

    protected function setUp(): void
    {
        $this->twoFactorFirewallConfig = $this->createMock(TwoFactorFirewallConfig::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->request = $this->createMock(Request::class);

        $this->listener = new TwoFactorFormListener($this->twoFactorFirewallConfig, $this->tokenStorage, $this->eventDispatcher);
    }

    private function stubHasSession(bool $hasSession): void
    {
        $this->request
            ->expects($this->any())
            ->method('hasSession')
            ->willReturn($hasSession);
    }

    private function stubToken(string $className): void
    {
        $this->tokenStorage
            ->expects($this->any())
            ->method('getToken')
            ->willReturn($this->createMock($className));
    }

    private function stubIsAuthFormPath(bool $isAuthFormRequest): void
    {
        $this->twoFactorFirewallConfig
            ->expects($this->any())
            ->method('isAuthFormRequest')
            ->with($this->request)
            ->willReturn($isAuthFormRequest);
    }

    private function expectNotDispatchEvent(): void
    {
        $this->eventDispatcher
            ->expects($this->never())
            ->method($this->anything());
    }

    private function createRequestEvent(): RequestEvent
    {
        $event = $this->createMock(RequestEvent::class);
        $event
            ->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->request);

        return $event;
    }

    /**
     * @test
     */
    public function onKernelRequest_hasNoSession_doNothing(): void
    {
        $this->stubHasSession(false);
        $this->stubToken(TwoFactorTokenInterface::class);
        $this->stubIsAuthFormPath(true);

        $this->expectNotDispatchEvent();

        $this->listener->onKernelRequest($this->createRequestEvent());
    }

    /**
     * @test
     */
    public function onKernelRequest_noTwoFactorToken_doNothing(): void
    {
        $this->stubHasSession(true);
        $this->stubToken(TokenInterface::class);
        $this->stubIsAuthFormPath(true);

        $this->expectNotDispatchEvent();

        $this->listener->onKernelRequest($this->createRequestEvent());
    }

    /**
     * @test
     */
    public function onKernelRequest_isNotAuthFormRequest_doNothing(): void
    {
        $this->stubHasSession(true);
        $this->stubToken(TwoFactorTokenInterface::class);
        $this->stubIsAuthFormPath(false);

        $this->expectNotDispatchEvent();

        $this->listener->onKernelRequest($this->createRequestEvent());
    }

    /**
     * @test
     */
    public function onKernelRequest_isAuthFormRequest_dispatchFormEvent(): void
    {
        $this->stubHasSession(true);
        $this->stubToken(TwoFactorTokenInterface::class);
        $this->stubIsAuthFormPath(true);

        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(TwoFactorAuthenticationEvent::class), TwoFactorAuthenticationEvents::FORM);

        $this->listener->onKernelRequest($this->createRequestEvent());
    }
}
