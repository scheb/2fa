<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\Http\Firewall;

use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\Authorization\TwoFactorAccessDecider;
use Scheb\TwoFactorBundle\Security\Http\Firewall\TwoFactorAccessListener;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvent;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvents;
use Scheb\TwoFactorBundle\Security\TwoFactor\TwoFactorFirewallConfig;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class TwoFactorAccessListenerTest extends TestCase
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
     * @var MockObject|TwoFactorAccessDecider
     */
    private $twoFactorAccessDecider;

    /**
     * @var MockObject|EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var TwoFactorAccessListener
     */
    private $accessListener;

    /**
     * @var MockObject|Request
     */
    private $request;

    protected function setUp(): void
    {
        $this->twoFactorFirewallConfig = $this->createMock(TwoFactorFirewallConfig::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->twoFactorAccessDecider = $this->createMock(TwoFactorAccessDecider::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->request = $this->createMock(Request::class);

        $this->accessListener = new TwoFactorAccessListener(
            $this->twoFactorFirewallConfig,
            $this->tokenStorage,
            $this->twoFactorAccessDecider,
            $this->eventDispatcher
        );
    }

    /**
     * @return MockObject|TwoFactorTokenInterface
     */
    private function createTwoFactorToken(): MockObject
    {
        return $this->createMock(TwoFactorTokenInterface::class);
    }

    private function createRequestEvent(): RequestEvent
    {
        $requestEvent = $this->createMock(RequestEvent::class);
        $requestEvent
            ->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->request);

        return $requestEvent;
    }

    private function stubTokenStorageHasToken(TokenInterface $token): void
    {
        $this->tokenStorage
            ->expects($this->any())
            ->method('getToken')
            ->willReturn($token);
    }

    private function stubRequestIsCheckPath(bool $isCheckPath): void
    {
        $this->twoFactorFirewallConfig
            ->expects($this->any())
            ->method('isCheckPathRequest')
            ->with($this->request)
            ->willReturn($isCheckPath);
    }

    private function stubRequestIsAuthPath(bool $isCheckPath): void
    {
        $this->twoFactorFirewallConfig
            ->expects($this->any())
            ->method('isAuthFormRequest')
            ->with($this->request)
            ->willReturn($isCheckPath);
    }

    /**
     * @test
     */
    public function supports_notTwoFactorToken_returnFalse(): void
    {
        $this->stubTokenStorageHasToken($this->createMock(TokenInterface::class));
        $this->assertFalse($this->accessListener->supports($this->request));
    }

    /**
     * @test
     */
    public function supports_isTwoFactorToken_returnTrue(): void
    {
        $this->stubTokenStorageHasToken($this->createTwoFactorToken());
        $this->assertTrue($this->accessListener->supports($this->request));
    }

    /**
     * @test
     */
    public function authenticate_isCheckPathRequest_doNothing(): void
    {
        $this->stubTokenStorageHasToken($this->createTwoFactorToken());
        $this->stubRequestIsCheckPath(true);
        $this->stubRequestIsAuthPath(false);

        $this->twoFactorAccessDecider
            ->expects($this->never())
            ->method($this->anything());

        $this->accessListener->authenticate($this->createRequestEvent());
    }

    /**
     * @test
     */
    public function authenticate_isAuthFormRequest_dispatchEvent(): void
    {
        $this->stubTokenStorageHasToken($this->createTwoFactorToken());
        $this->stubRequestIsCheckPath(false);
        $this->stubRequestIsAuthPath(true);

        $this->eventDispatcher
            ->expects($this->any())
            ->method('dispatch')
            ->with($this->isInstanceOf(TwoFactorAuthenticationEvent::class), TwoFactorAuthenticationEvents::FORM);

        $this->twoFactorAccessDecider
            ->expects($this->never())
            ->method($this->anything());

        $this->accessListener->authenticate($this->createRequestEvent());
    }

    /**
     * @test
     */
    public function authenticate_noSpecialPath_checkIfAccessible(): void
    {
        $token = $this->createTwoFactorToken();
        $this->stubTokenStorageHasToken($token);
        $this->stubRequestIsCheckPath(false);
        $this->stubRequestIsAuthPath(false);

        $this->twoFactorAccessDecider
            ->expects($this->once())
            ->method('isAccessible')
            ->with($this->request, $token)
            ->willReturn(true);

        $this->accessListener->authenticate($this->createRequestEvent());
    }

    /**
     * @test
     */
    public function authenticate_isAccessDenied_throwAccessDeniedException(): void
    {
        $token = $this->createTwoFactorToken();
        $this->stubTokenStorageHasToken($token);
        $this->stubRequestIsCheckPath(false);
        $this->stubRequestIsAuthPath(false);

        $this->twoFactorAccessDecider
            ->expects($this->once())
            ->method('isAccessible')
            ->with($this->request, $token)
            ->willReturn(false);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('User is in a two-factor authentication process');

        $this->accessListener->authenticate($this->createRequestEvent());
    }
}
