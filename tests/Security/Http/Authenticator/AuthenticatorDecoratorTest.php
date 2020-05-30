<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\Http\Authenticator;

use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\Authentication\Provider\AuthenticationProviderDecorator;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\Http\Authenticator\AuthenticatorDecorator;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextFactoryInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Handler\AuthenticationHandlerInterface;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\InteractiveAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;

class AuthenticatorDecoratorTest extends TestCase
{
    /**
     * @var MockObject|AuthenticatorInterface
     */
    private $decoratedAuthenticator;

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
    private $decorator;

    protected function setUp(): void
    {
        $this->requireSymfony5_1();

        $this->decoratedAuthenticator = $this->createMock(AuthenticatorInterface::class);
        $this->twoFactorAuthenticationHandler = $this->createMock(AuthenticationHandlerInterface::class);
        $this->authenticationContextFactory = $this->createMock(AuthenticationContextFactoryInterface::class);

        $this->requestStack = $this->createMock(RequestStack::class);
        $this->requestStack
            ->expects($this->any())
            ->method('getMasterRequest')
            ->willReturn($this->createMock(Request::class));

        $this->decorator = new AuthenticatorDecorator(
            $this->decoratedAuthenticator,
            $this->twoFactorAuthenticationHandler,
            $this->authenticationContextFactory,
            $this->requestStack
        );
    }

    private function stubDecoratedAuthenticatorCreatesToken(?MockObject $passport): void
    {
        $this->decoratedAuthenticator
            ->expects($this->any())
            ->method('createAuthenticatedToken')
            ->willReturn($passport);
    }

    /**
     * @test
     * @dataProvider provideSupportsResult
     */
    public function supports_anyRequest_returnResultFromDecoratedAuthenticator(bool $result): void
    {
        $request = $this->createMock(Request::class);

        $this->decoratedAuthenticator
            ->expects($this->once())
            ->method('supports')
            ->with($request)
            ->willReturn($result);

        $returnValue = $this->decorator->supports($request);
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
    public function authenticate_anyRequest_returnResultFromDecoratedAuthenticator(): void
    {
        $request = $this->createMock(Request::class);
        $result = $this->createMock(PassportInterface::class);

        $this->decoratedAuthenticator
            ->expects($this->once())
            ->method('authenticate')
            ->with($request)
            ->willReturn($result);

        $returnValue = $this->decorator->authenticate($request);
        $this->assertSame($result, $returnValue);
    }

    /**
     * @test
     */
    public function createAuthenticatedToken_createsTwoFactorToken_returnThatToken(): void
    {
        $token = $this->createMock(TwoFactorTokenInterface::class);
        $this->stubDecoratedAuthenticatorCreatesToken($token);

        $this->twoFactorAuthenticationHandler
            ->expects($this->never())
            ->method($this->anything());

        $passport = $this->createMock(PassportInterface::class);
        $returnValue = $this->decorator->createAuthenticatedToken($passport, 'firewallName');
        $this->assertSame($token, $returnValue);
    }

    /**
     * @test
     */
    public function createAuthenticatedToken_firewallSupportsTwoFactorAuthentication_createAuthenticationContext(): void
    {
        $authenticatedToken = $this->createMock(TokenInterface::class);
        $this->stubDecoratedAuthenticatorCreatesToken($authenticatedToken);

        $this->authenticationContextFactory
            ->expects($this->once())
            ->method('create')
            ->with($this->isInstanceOf(Request::class), $authenticatedToken, 'firewallName');

        $passport = $this->createMock(PassportInterface::class);
        $this->decorator->createAuthenticatedToken($passport, 'firewallName');
    }

    /**
     * @test
     */
    public function createAuthenticatedToken_authenticatedToken_returnTokenFromTwoFactorAuthenticationHandler(): void
    {
        $authenticatedToken = $this->createMock(TokenInterface::class);
        $this->stubDecoratedAuthenticatorCreatesToken($authenticatedToken);

        $twoFactorToken = $this->createMock(TwoFactorTokenInterface::class);
        $this->twoFactorAuthenticationHandler
            ->expects($this->once())
            ->method('beginTwoFactorAuthentication')
            ->with($this->isInstanceOf(AuthenticationContextInterface::class))
            ->willReturn($twoFactorToken);

        $passport = $this->createMock(PassportInterface::class);
        $returnValue = $this->decorator->createAuthenticatedToken($passport, 'firewallName');
        $this->assertSame($twoFactorToken, $returnValue);
    }

    /**
     * @test
     */
    public function onAuthenticationSuccess_anyCall_returnResultFromDecoratedAuthenticator(): void
    {
        $request = $this->createMock(Request::class);
        $token = $this->createMock(TokenInterface::class);
        $response = $this->createMock(Response::class);

        $this->decoratedAuthenticator
            ->expects($this->once())
            ->method('onAuthenticationSuccess')
            ->with($request, $token, 'firewallName')
            ->willReturn($response);

        $returnValue = $this->decorator->onAuthenticationSuccess($request, $token, 'firewallName');
        $this->assertSame($response, $returnValue);
    }

    /**
     * @test
     */
    public function onAuthenticationFailure_anyCall_returnResultFromDecoratedAuthenticator(): void
    {
        $request = $this->createMock(Request::class);
        $exception = $this->createMock(AuthenticationException::class);
        $response = $this->createMock(Response::class);

        $this->decoratedAuthenticator
            ->expects($this->once())
            ->method('onAuthenticationFailure')
            ->with($request, $exception)
            ->willReturn($response);

        $returnValue = $this->decorator->onAuthenticationFailure($request, $exception);
        $this->assertSame($response, $returnValue);
    }

    /**
     * @test
     */
    public function isInteractive_notImplementsInteractiveAuthenticatorInterface_returnFalse(): void
    {
        $decorator = new AuthenticatorDecorator(
            $this->createMock(AuthenticatorInterface::class),
            $this->twoFactorAuthenticationHandler,
            $this->authenticationContextFactory,
            $this->requestStack
        );

        $this->assertFalse($decorator->isInteractive());
    }

    /**
     * @test
     * @dataProvider provideIsInteractiveResult
     */
    public function isInteractive_implementsInteractiveAuthenticatorInterface_returnResultFromDecoratedAuthenticator(bool $result): void
    {
        $decoratedAuthenticator = $this->createMock(InteractiveAuthenticatorInterface::class);
        $decoratedAuthenticator
            ->expects($this->any())
            ->method('isInteractive')
            ->willReturn($result);

        $decorator = new AuthenticatorDecorator(
            $decoratedAuthenticator,
            $this->twoFactorAuthenticationHandler,
            $this->authenticationContextFactory,
            $this->requestStack
        );

        $this->assertEquals($result, $decorator->isInteractive());
    }

    public function provideIsInteractiveResult(): array
    {
        return [
            [true],
            [false],
        ];
    }
}
