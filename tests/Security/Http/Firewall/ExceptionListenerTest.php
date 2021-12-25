<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\Http\Firewall;

use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\Http\Authentication\AuthenticationRequiredHandlerInterface;
use Scheb\TwoFactorBundle\Security\Http\Firewall\ExceptionListener;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvent;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvents;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Throwable;

class ExceptionListenerTest extends TestCase
{
    private const FIREWALL_NAME = 'firewallName';

    private MockObject|TokenStorageInterface $tokenStorage;
    private MockObject|AuthenticationRequiredHandlerInterface $authenticationRequiredHandler;
    private MockObject|EventDispatcherInterface $eventDispatcher;
    private MockObject|Request $request;
    private MockObject|Response $response;
    private ExceptionListener $listener;

    protected function setUp(): void
    {
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->authenticationRequiredHandler = $this->createMock(AuthenticationRequiredHandlerInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->request = $this->createMock(Request::class);
        $this->response = $this->createMock(Response::class);

        $this->listener = new ExceptionListener(
            self::FIREWALL_NAME,
            $this->tokenStorage,
            $this->authenticationRequiredHandler,
            $this->eventDispatcher
        );
    }

    private function stubTokenStorageHasToken(TokenInterface $token): void
    {
        $this->tokenStorage
            ->expects($this->any())
            ->method('getToken')
            ->willReturn($token);
    }

    private function expectAuthenticationRequireEvent(): void
    {
        $this->eventDispatcher
            ->expects($this->any())
            ->method('dispatch')
            ->with($this->isInstanceOf(TwoFactorAuthenticationEvent::class), TwoFactorAuthenticationEvents::REQUIRE);
    }

    private function expectAuthenticationRequiredHandlerCreateResponse(TokenInterface $token, Response $response): void
    {
        $this->authenticationRequiredHandler
            ->expects($this->any())
            ->method('onAuthenticationRequired')
            ->with($this->identicalTo($this->request), $token)
            ->willReturn($response);
    }

    private function createTwoFactorToken(string $firewallName): MockObject|TwoFactorTokenInterface
    {
        $token = $this->createMock(TwoFactorTokenInterface::class);
        $token
            ->expects($this->any())
            ->method('getFirewallName')
            ->willReturn($firewallName);

        return $token;
    }

    private function createExceptionEvent(Throwable $exception): ExceptionEvent
    {
        return new ExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            $this->request,
            HttpKernelInterface::MASTER_REQUEST,
            $exception
        );
    }

    private function assertNotHasResponse(ExceptionEvent $event): void
    {
        $this->assertNull($event->getResponse());
    }

    private function assertHasResponse(ExceptionEvent $event, Response $response): void
    {
        $this->assertSame($response, $event->getResponse());
    }

    /**
     * @test
     */
    public function onKernelException_notAccessDeniedException_doNothing(): void
    {
        $this->stubTokenStorageHasToken($this->createTwoFactorToken(self::FIREWALL_NAME));
        $event = $this->createExceptionEvent(new InvalidArgumentException());

        $this->listener->onKernelException($event);
        $this->assertNotHasResponse($event);
    }

    /**
     * @test
     */
    public function onKernelException_notTwoFactorToken_doNothing(): void
    {
        $this->stubTokenStorageHasToken($this->createMock(TokenInterface::class));
        $event = $this->createExceptionEvent(new AccessDeniedException());

        $this->listener->onKernelException($event);
        $this->assertNotHasResponse($event);
    }

    /**
     * @test
     */
    public function onKernelException_differentFirewall_doNothing(): void
    {
        $this->stubTokenStorageHasToken($this->createTwoFactorToken('differentFirewallName'));
        $event = $this->createExceptionEvent(new AccessDeniedException());

        $this->listener->onKernelException($event);
        $this->assertNotHasResponse($event);
    }

    /**
     * @test
     * @dataProvider provideExceptions
     */
    public function onKernelException_allConditionsFulfilled_displayRequireEventSetResponse(Throwable $exception): void
    {
        $token = $this->createTwoFactorToken(self::FIREWALL_NAME);
        $this->stubTokenStorageHasToken($token);
        $event = $this->createExceptionEvent($exception);

        $this->expectAuthenticationRequiredHandlerCreateResponse($token, $this->response);
        $this->expectAuthenticationRequireEvent();

        $this->listener->onKernelException($event);
        $this->assertHasResponse($event, $this->response);
    }

    /**
     * @return array<string,array<mixed>>
     */
    public function provideExceptions(): array
    {
        return [
            'AccessDeniedException' => [new AccessDeniedException()],
            'nested AccessDeniedException' => [new Exception('msg', 0, new AccessDeniedException())],
        ];
    }
}
