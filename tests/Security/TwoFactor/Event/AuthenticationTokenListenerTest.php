<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Event;

use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\Http\Authenticator\TwoFactorAuthenticator;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextFactoryInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Condition\TwoFactorConditionRegistry;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\AuthenticationTokenListener;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderInitiator;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Event\AuthenticationTokenCreatedEvent;

class AuthenticationTokenListenerTest extends TestCase
{
    private const FIREWALL_NAME = 'firewallName';

    private MockObject|TwoFactorConditionRegistry $twoFactorConditionRegistry;
    private MockObject|TwoFactorProviderInitiator $twoFactorProviderInitiator;
    private MockObject|AuthenticationContextFactoryInterface $authenticationContextFactory;
    private AuthenticationTokenListener $listener;

    protected function setUp(): void
    {
        $this->twoFactorConditionRegistry = $this->createMock(TwoFactorConditionRegistry::class);
        $this->twoFactorProviderInitiator = $this->createMock(TwoFactorProviderInitiator::class);
        $this->authenticationContextFactory = $this->createMock(AuthenticationContextFactoryInterface::class);

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->expects($this->any())
            ->method('getMainRequest')
            ->willReturn($this->createMock(Request::class));

        $this->listener = new AuthenticationTokenListener(
            self::FIREWALL_NAME,
            $this->twoFactorConditionRegistry,
            $this->twoFactorProviderInitiator,
            $this->authenticationContextFactory,
            $requestStack
        );
    }

    private function createEvent(MockObject $token): MockObject|AuthenticationTokenCreatedEvent
    {
        $event = $this->createMock(AuthenticationTokenCreatedEvent::class);
        $event
            ->expects($this->any())
            ->method('getAuthenticatedToken')
            ->willReturn($token);

        $passport = $this->createMock(Passport::class);
        $event
            ->expects($this->any())
            ->method('getPassport')
            ->willReturn($passport);

        return $event;
    }

    private function stubTwoFactorConditionsFulfilled(bool $fulfilled): void
    {
        $this->twoFactorConditionRegistry
            ->expects($this->any())
            ->method('shouldPerformTwoFactorAuthentication')
            ->willReturn($fulfilled);
    }

    private function expectTwoFactorAuthenticationHandlerNeverCalled(): void
    {
        $this->twoFactorProviderInitiator
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
        $this->stubTwoFactorConditionsFulfilled(true);
        $event = $this->createEvent($this->createMock(TwoFactorTokenInterface::class));

        $this->expectTwoFactorAuthenticationHandlerNeverCalled();
        $this->expectTokenNotExchanged($event);

        $this->listener->onAuthenticationTokenCreated($event);
    }

    /**
     * @test
     */
    public function onAuthenticationTokenCreated_tokenFlagged2faComplete_notChangeToken(): void
    {
        $this->stubTwoFactorConditionsFulfilled(true);
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
    public function createAuthenticatedToken_preconditionsFulfilled_createAuthenticationContext(): void
    {
        $this->stubTwoFactorConditionsFulfilled(true);
        $authenticatedToken = $this->createMock(TokenInterface::class);
        $event = $this->createEvent($authenticatedToken);

        $this->authenticationContextFactory
            ->expects($this->once())
            ->method('create')
            ->with($this->isInstanceOf(Request::class), $authenticatedToken, $this->isInstanceOf(Passport::class), self::FIREWALL_NAME);

        $this->listener->onAuthenticationTokenCreated($event);
    }

    /**
     * @test
     */
    public function onAuthenticationTokenCreated_twoFactorConditionsUnfulfilled_notInitiate(): void
    {
        $this->stubTwoFactorConditionsFulfilled(false);
        $event = $this->createEvent($this->createMock(TokenInterface::class));

        $this->twoFactorProviderInitiator
            ->expects($this->never())
            ->method('beginTwoFactorAuthentication');

        $this->expectTokenNotExchanged($event);
        $this->listener->onAuthenticationTokenCreated($event);
    }

    /**
     * @test
     */
    public function onAuthenticationTokenCreated_twoFactorConditionsFulfilled_initiateTwoFactorAuthentication(): void
    {
        $this->stubTwoFactorConditionsFulfilled(true);
        $event = $this->createEvent($this->createMock(TokenInterface::class));

        $this->twoFactorProviderInitiator
            ->expects($this->once())
            ->method('beginTwoFactorAuthentication')
            ->with($this->isInstanceOf(AuthenticationContextInterface::class))
            ->willReturn(null);

        $this->listener->onAuthenticationTokenCreated($event);
    }

    /**
     * @test
     */
    public function onAuthenticationTokenCreated_noTwoFactorProvidersAvailable_keepSecurityToken(): void
    {
        $this->stubTwoFactorConditionsFulfilled(true);
        $event = $this->createEvent($this->createMock(TokenInterface::class));

        $this->twoFactorProviderInitiator
            ->expects($this->any())
            ->method('beginTwoFactorAuthentication')
            ->willReturn(null);

        $this->expectTokenNotExchanged($event);
        $this->listener->onAuthenticationTokenCreated($event);
    }

    /**
     * @test
     */
    public function onAuthenticationTokenCreated_hasTwoFactorProvidersAvailable_changeSecurityToken(): void
    {
        $this->stubTwoFactorConditionsFulfilled(true);
        $event = $this->createEvent($this->createMock(TokenInterface::class));

        $twoFactorToken = $this->createMock(TwoFactorTokenInterface::class);
        $this->twoFactorProviderInitiator
            ->expects($this->any())
            ->method('beginTwoFactorAuthentication')
            ->willReturn($twoFactorToken);

        $this->expectTokenExchanged($event, $twoFactorToken);
        $this->listener->onAuthenticationTokenCreated($event);
    }
}
