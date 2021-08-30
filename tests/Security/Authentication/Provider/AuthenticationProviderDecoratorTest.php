<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\Authentication\Provider;

use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\Authentication\Provider\AuthenticationProviderDecorator;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextFactoryInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Handler\AuthenticationHandlerInterface;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Bundle\SecurityBundle\Security\FirewallConfig;
use Symfony\Bundle\SecurityBundle\Security\FirewallMap;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class AuthenticationProviderDecoratorTest extends TestCase
{
    /**
     * @var MockObject|AuthenticationProviderInterface
     */
    private $decoratedAuthenticationProvider;

    /**
     * @var MockObject|AuthenticationHandlerInterface
     */
    private $twoFactorAuthenticationHandler;

    /**
     * @var MockObject|AuthenticationContextFactoryInterface
     */
    private $authenticationContextFactory;

    /**
     * @var MockObject|FirewallMap
     */
    private $firewallMap;

    /**
     * @var MockObject|RequestStack
     */
    private $requestStack;

    /**
     * @var AuthenticationProviderDecorator
     */
    private $decorator;

    protected function setUp(): void
    {
        $this->decoratedAuthenticationProvider = $this->createMock(AuthenticationProviderInterface::class);
        $this->twoFactorAuthenticationHandler = $this->createMock(AuthenticationHandlerInterface::class);
        $this->authenticationContextFactory = $this->createMock(AuthenticationContextFactoryInterface::class);
        $this->firewallMap = $this->createMock(FirewallMap::class);

        $this->requestStack = $this->createMock(RequestStack::class);
        // Compatibility for Symfony >= 5.3
        if (method_exists(RequestStack::class, 'getMainRequest')) {
            $method = 'getMainRequest';
        } else {
            $method = 'getMasterRequest';
        }
        $this->requestStack
            ->expects($this->any())
            ->method($method)
            ->willReturn($this->createMock(Request::class));

        $this->decorator = new AuthenticationProviderDecorator(
            $this->decoratedAuthenticationProvider,
            $this->twoFactorAuthenticationHandler,
            $this->authenticationContextFactory,
            $this->firewallMap,
            $this->requestStack
        );
    }

    private function stubDecoratedProviderReturnsToken(?MockObject $token): void
    {
        $this->decoratedAuthenticationProvider
            ->expects($this->any())
            ->method('authenticate')
            ->willReturn($token);
    }

    private function stubFirewallHasListenersRegistered(array $listenerKeys): void
    {
        $firewallConfig = $this->createFirewallConfig($listenerKeys);
        $this->firewallMap
            ->expects($this->any())
            ->method('getFirewallConfig')
            ->willReturn($firewallConfig);
    }

    private function createFirewallConfig(array $listenerKeys): FirewallConfig
    {
        return new FirewallConfig(
            'firewallName',
            'userChecker',
            null,
            true,
            false,
            null,
            null,
            null,
            null,
            null,
            $listenerKeys
        );
    }

    /**
     * @test
     * @dataProvider provideSupportsResult
     */
    public function supports_anyToken_returnResultFromDecoratedProvider(bool $result): void
    {
        $token = $this->createMock(TokenInterface::class);
        $this->stubDecoratedProviderReturnsToken($token);

        $this->decoratedAuthenticationProvider
            ->expects($this->once())
            ->method('supports')
            ->with($token)
            ->willReturn($result);

        $returnValue = $this->decorator->supports($token);
        $this->assertSame($result, $returnValue);
    }

    public function provideSupportsResult(): array
    {
        return [
            [true],
            [false],
        ];
    }

    /**
     * @test
     */
    public function authenticate_refreshAuthenticatedToken_dontStartTwoFactorAuthentication(): void
    {
        $originalAuthenticatedToken = $this->createMock(TokenInterface::class);
        $originalAuthenticatedToken
            ->expects($this->any())
            ->method('isAuthenticated')
            ->willReturn(true);

        $refreshedAuthenticatedToken = $this->createMock(TokenInterface::class);
        $this->stubDecoratedProviderReturnsToken($refreshedAuthenticatedToken);

        $this->twoFactorAuthenticationHandler
            ->expects($this->never())
            ->method($this->anything());

        $returnValue = $this->decorator->authenticate($originalAuthenticatedToken);
        $this->assertSame($refreshedAuthenticatedToken, $returnValue);
    }

    /**
     * @test
     * @dataProvider provideIgnoredToken
     */
    public function authenticate_ignoredToken_returnThatToken($ignoredToken): void
    {
        $this->stubDecoratedProviderReturnsToken($ignoredToken);

        $this->twoFactorAuthenticationHandler
            ->expects($this->never())
            ->method($this->anything());

        $returnValue = $this->decorator->authenticate($this->createMock(TokenInterface::class));
        $this->assertSame($ignoredToken, $returnValue);
    }

    public function provideIgnoredToken(): array
    {
        return [
            [$this->createMock(AnonymousToken::class)],
            [$this->createMock(TwoFactorTokenInterface::class)],
        ];
    }

    /**
     * @test
     */
    public function authenticate_firewallNotSupportsTwoFactorAuthentication_returnThatToken(): void
    {
        $authenticatedToken = $this->createMock(TokenInterface::class);
        $this->stubDecoratedProviderReturnsToken($authenticatedToken);
        $this->stubFirewallHasListenersRegistered(['form_login', 'anonymous']);

        $returnValue = $this->decorator->authenticate($this->createMock(TokenInterface::class));
        $this->assertSame($authenticatedToken, $returnValue);
    }

    /**
     * @test
     */
    public function authenticate_firewallSupportsTwoFactorAuthentication_createAuthenticationContext(): void
    {
        $authenticatedToken = $this->createMock(TokenInterface::class);
        $this->stubDecoratedProviderReturnsToken($authenticatedToken);
        $this->stubFirewallHasListenersRegistered(['form_login', 'anonymous', 'two_factor']);

        $this->authenticationContextFactory
            ->expects($this->once())
            ->method('create')
            ->with($this->isInstanceOf(Request::class), $authenticatedToken, 'firewallName');

        $this->decorator->authenticate($this->createMock(TokenInterface::class));
    }

    /**
     * @test
     */
    public function authenticate_authenticatedToken_returnTokenFromTwoFactorAuthenticationHandler(): void
    {
        $authenticatedToken = $this->createMock(TokenInterface::class);
        $this->stubDecoratedProviderReturnsToken($authenticatedToken);
        $this->stubFirewallHasListenersRegistered(['form_login', 'anonymous', 'two_factor']);

        $twoFactorToken = $this->createMock(TwoFactorTokenInterface::class);
        $this->twoFactorAuthenticationHandler
            ->expects($this->once())
            ->method('beginTwoFactorAuthentication')
            ->with($this->isInstanceOf(AuthenticationContextInterface::class))
            ->willReturn($twoFactorToken);

        $returnValue = $this->decorator->authenticate($this->createMock(TokenInterface::class));
        $this->assertSame($twoFactorToken, $returnValue);
    }
}
