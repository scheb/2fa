<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Event;

use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\Authentication\Provider\AuthenticationProviderDecorator;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\Http\Authenticator\TwoFactorAuthenticator;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextFactoryInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\AuthenticationTokenListener;
use Scheb\TwoFactorBundle\Security\TwoFactor\Handler\AuthenticationHandlerInterface;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Event\AuthenticationTokenCreatedEvent;

class AuthenticationTokenListenerTest extends TestCase
{
    /**
     * @var MockObject|AuthenticationHandlerInterface
     */
    private $twoFactorAuthenticationHandler;

    /**
     * @var MockObject|AuthenticationContextFactoryInterface
     */
    private $authenticationContextFactory;

    /**
     * @var MockObject|RequestStack
     */
    private $requestStack;

    /**
     * @var AuthenticationProviderDecorator
     */
    private $listener;

    protected function setUp(): void
    {
        $this->requireAtLeastSymfony5_1();

        $this->twoFactorAuthenticationHandler = $this->createMock(AuthenticationHandlerInterface::class);
        $this->authenticationContextFactory = $this->createMock(AuthenticationContextFactoryInterface::class);

        $this->requestStack = $this->createMock(RequestStack::class);
        $this->requestStack
            ->expects($this->any())
            ->method('getMasterRequest')
            ->willReturn($this->createMock(Request::class));

        $this->listener = new AuthenticationTokenListener(
            'firewallName',
            $this->twoFactorAuthenticationHandler,
            $this->authenticationContextFactory,
            $this->requestStack
        );
    }

    /**
     * @return MockObject|AuthenticationTokenCreatedEvent
     */
    private function createEvent(MockObject $token): MockObject
    {
        $event = $this->createMock(AuthenticationTokenCreatedEvent::class);
        $event
            ->expects($this->any())
            ->method('getAuthenticatedToken')
            ->willReturn($token);

        return $event;
    }

    private function expectTwoFactorAuthenticationHandlerNeverCalled(): void
    {
        $this->twoFactorAuthenticationHandler
            ->expects($this->never())
            ->method($this->anything());
    }

    private function expectTokenNotExchanged(MockObject $event): void
    {
        $event
            ->expects($this->never())
            ->method('setAuthenticatedToken');
    }

    private function expectTokenExchanged(MockObject $event, TokenInterface $expectedToken): void
    {
        $event
            ->expects($this->once())
            ->method('setAuthenticatedToken')
            ->with($expectedToken);
    }

    /**
     * @test
     */
    public function onAuthenticationTokenCreated_isTwoFactorToken_notChangeToken(): void
    {
        $authenticatedToken = $this->createMock(TwoFactorTokenInterface::class);
        $event = $this->createEvent($authenticatedToken);

        $this->expectTwoFactorAuthenticationHandlerNeverCalled();
        $this->expectTokenNotExchanged($event);

        $this->listener->onAuthenticationTokenCreated($event);
    }

    /**
     * @test
     */
    public function onAuthenticationTokenCreated_tokenFlagged2faComplete_notChangeToken(): void
    {
        $authenticatedToken = $this->createMock(TokenInterface::class);
        $authenticatedToken
            ->expects($this->any())
            ->method('hasAttribute')
            ->with(TwoFactorAuthenticator::FLAG_2FA_COMPLETE)
            ->willReturn(true);
        $event = $this->createEvent($authenticatedToken);

        $this->expectTwoFactorAuthenticationHandlerNeverCalled();
        $this->expectTokenNotExchanged($event);

        $this->listener->onAuthenticationTokenCreated($this->createEvent($authenticatedToken));
    }

    /**
     * @test
     */
    public function createAuthenticatedToken_tokenMustBeChecked_createAuthenticationContext(): void
    {
        $authenticatedToken = $this->createMock(TokenInterface::class);
        $event = $this->createEvent($authenticatedToken);

        $this->authenticationContextFactory
            ->expects($this->once())
            ->method('create')
            ->with($this->isInstanceOf(Request::class), $authenticatedToken, 'firewallName');

        $this->listener->onAuthenticationTokenCreated($event);
    }

    /**
     * @test
     */
    public function onAuthenticationTokenCreated_noTwoFactorNecessary_notChangeToken(): void
    {
        $authenticatedToken = $this->createMock(TokenInterface::class);
        $event = $this->createEvent($authenticatedToken);

        $this->twoFactorAuthenticationHandler
            ->expects($this->once())
            ->method('beginTwoFactorAuthentication')
            ->with($this->isInstanceOf(AuthenticationContextInterface::class))
            ->willReturn($authenticatedToken);

        $this->expectTokenNotExchanged($event);
        $this->listener->onAuthenticationTokenCreated($event);
    }

    /**
     * @test
     */
    public function onAuthenticationTokenCreated_twoFactorRequired_setTokenFromTwoFactorAuthenticationHandler(): void
    {
        $authenticatedToken = $this->createMock(TokenInterface::class);
        $event = $this->createEvent($authenticatedToken);

        $twoFactorToken = $this->createMock(TwoFactorTokenInterface::class);
        $this->twoFactorAuthenticationHandler
            ->expects($this->once())
            ->method('beginTwoFactorAuthentication')
            ->with($this->isInstanceOf(AuthenticationContextInterface::class))
            ->willReturn($twoFactorToken);

        $this->expectTokenExchanged($event, $twoFactorToken);
        $this->listener->onAuthenticationTokenCreated($event);
    }
}
