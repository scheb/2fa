<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\Http\Authenticator;

use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\Http\Authentication\AuthenticationRequiredHandlerInterface;
use Scheb\TwoFactorBundle\Security\Http\Authenticator\Passport\Badge\TrustedDeviceBadge;
use Scheb\TwoFactorBundle\Security\Http\Authenticator\Passport\Credentials\TwoFactorCodeCredentials;
use Scheb\TwoFactorBundle\Security\Http\Authenticator\TwoFactorAuthenticator;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvent;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvents;
use Scheb\TwoFactorBundle\Security\TwoFactor\TwoFactorFirewallConfig;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use function array_map;
use function assert;
use function count;
use function method_exists;

class TwoFactorAuthenticatorTest extends TestCase
{
    private const FIREWALL_NAME = 'firewallName';
    private const CODE = '2faCode';
    private const CSRF_TOKEN = 'csrfToken';
    private const CSRF_TOKEN_ID = 'csrfTokenId';
    private const USERNAME = 'username';

    private MockObject|TwoFactorFirewallConfig $twoFactorFirewallConfig;
    private MockObject|TokenStorageInterface $tokenStorage;
    private MockObject|AuthenticationSuccessHandlerInterface $successHandler;
    private MockObject|AuthenticationFailureHandlerInterface $failureHandler;
    private MockObject|AuthenticationRequiredHandlerInterface $authenticationRequiredHandler;
    private MockObject|EventDispatcherInterface $eventDispatcher;
    private MockObject|Request $request;
    private TwoFactorAuthenticator $authenticator;

    protected function setUp(): void
    {
        $this->twoFactorFirewallConfig = $this->createMock(TwoFactorFirewallConfig::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->successHandler = $this->createMock(AuthenticationSuccessHandlerInterface::class);
        $this->failureHandler = $this->createMock(AuthenticationFailureHandlerInterface::class);
        $this->authenticationRequiredHandler = $this->createMock(AuthenticationRequiredHandlerInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->request = $this->createMock(Request::class);

        $this->twoFactorFirewallConfig
            ->expects($this->any())
            ->method('getAuthCodeFromRequest')
            ->with($this->request)
            ->willReturn(self::CODE);

        $this->twoFactorFirewallConfig
            ->expects($this->any())
            ->method('getCsrfTokenFromRequest')
            ->with($this->request)
            ->willReturn(self::CSRF_TOKEN);

        $this->twoFactorFirewallConfig
            ->expects($this->any())
            ->method('getCsrfTokenId')
            ->willReturn(self::CSRF_TOKEN_ID);

        $this->authenticator = new TwoFactorAuthenticator(
            $this->twoFactorFirewallConfig,
            $this->tokenStorage,
            $this->successHandler,
            $this->failureHandler,
            $this->authenticationRequiredHandler,
            $this->eventDispatcher,
            $this->createMock(LoggerInterface::class)
        );
    }

    public function stubIsMultiFactorFirewall(bool $multiFactor): void
    {
        $this->twoFactorFirewallConfig
            ->expects($this->any())
            ->method('isMultiFactor')
            ->willReturn($multiFactor);
    }

    private function stubCsrfProtectionEnabled(bool $enabled): void
    {
        $this->twoFactorFirewallConfig
            ->expects($this->any())
            ->method('isCsrfProtectionEnabled')
            ->willReturn($enabled);
    }

    private function stubRequestHasTrustedDeviceParameter(bool $hasTrustedDeviceParam): void
    {
        $this->twoFactorFirewallConfig
            ->expects($this->any())
            ->method('hasTrustedDeviceParameterInRequest')
            ->with($this->request)
            ->willReturn($hasTrustedDeviceParam);
    }

    private function stubRememberMeSetsTrusted(bool $rememberMeSetsTrusted): void
    {
        $this->twoFactorFirewallConfig
            ->expects($this->any())
            ->method('isRememberMeSetsTrusted')
            ->willReturn($rememberMeSetsTrusted);
    }

    private function createTwoFactorToken(?TokenInterface $authenticatedToken = null, bool $allProvidersAuthenticated = false): MockObject|TwoFactorTokenInterface
    {
        $user = $this->createMock(UserInterface::class);
        // Compatibility for Symfony < 6.0
        if (method_exists(UserInterface::class, 'getUserIdentifier')) {
            $user
                ->expects($this->any())
                ->method('getUserIdentifier')
                ->willReturn(self::USERNAME);
        }

        $token = $this->createMock(TwoFactorTokenInterface::class);
        $token
            ->expects($this->any())
            ->method('getUser')
            ->willReturn($user);
        $token
            ->expects($this->any())
            ->method('getAuthenticatedToken')
            ->willReturn($authenticatedToken ?? $this->createMock(TokenInterface::class));
        $token
            ->expects($this->any())
            ->method('allTwoFactorProvidersAuthenticated')
            ->willReturn($allProvidersAuthenticated);

        return $token;
    }

    private function stubTokenStorageHasToken(TokenInterface $token): void
    {
        $this->tokenStorage
            ->expects($this->any())
            ->method('getToken')
            ->willReturn($token);
    }

    private function stubTokenStorageHasTwoFactorToken(): MockObject|TwoFactorTokenInterface
    {
        $token = $this->createTwoFactorToken();
        $this->stubTokenStorageHasToken($token);

        return $token;
    }

    private function createPassportWithTwoFactorCredentials(TwoFactorTokenInterface $twoFactorToken): MockObject|Passport
    {
        $credentials = $this->createMock(TwoFactorCodeCredentials::class);
        $credentials
            ->expects($this->any())
            ->method('getTwoFactorToken')
            ->willReturn($twoFactorToken);

        $passport = $this->createMock(Passport::class);
        $passport
            ->expects($this->any())
            ->method('getBadge')
            ->with(TwoFactorCodeCredentials::class)
            ->willReturn($credentials);

        return $passport;
    }

    private function stubIsCheckPath(bool $isCheckPath): void
    {
        $this->twoFactorFirewallConfig
            ->expects($this->any())
            ->method('isCheckPathRequest')
            ->with($this->request)
            ->willReturn($isCheckPath);
    }

    /**
     * @param string[] $events
     */
    private function expectDispatchEvents(array $events): void
    {
        $withArguments = array_map(function ($event): array {
            return [$this->isInstanceOf(TwoFactorAuthenticationEvent::class), $event];
        }, $events);

        $this->eventDispatcher
            ->expects($this->exactly(count($events)))
            ->method('dispatch')
            ->withConsecutive(...$withArguments);
    }

    private function expect2faCompleteFlagSet(MockObject $authenticatedToken): void
    {
        $authenticatedToken
            ->expects($this->any())
            ->method('setAttribute')
            ->with(TwoFactorAuthenticator::FLAG_2FA_COMPLETE, true);
    }

    /**
     * @test
     */
    public function supports_notCheckPath_returnFalse(): void
    {
        $this->stubIsCheckPath(false);
        $this->assertFalse($this->authenticator->supports($this->request));
    }

    /**
     * @test
     */
    public function supports_isCheckPath_returnTrue(): void
    {
        $this->stubIsCheckPath(true);
        $this->assertTrue($this->authenticator->supports($this->request));
    }

    /**
     * @test
     */
    public function authenticate_notTwoFactorToken_throwAccessDeniedException(): void
    {
        $this->stubTokenStorageHasToken($this->createMock(TokenInterface::class));
        $this->expectException(AccessDeniedException::class);
        $this->authenticator->authenticate($this->request);
    }

    /**
     * @test
     */
    public function authenticate_onRequest_dispatchAttemptEvent(): void
    {
        $this->stubTokenStorageHasTwoFactorToken();

        $this->expectDispatchEvents([TwoFactorAuthenticationEvents::ATTEMPT]);

        $this->authenticator->authenticate($this->request);
    }

    /**
     * @test
     */
    public function authenticate_onRequest_createTwoFactorPassportWithCredentials(): void
    {
        $this->stubTokenStorageHasTwoFactorToken();

        $returnValue = $this->authenticator->authenticate($this->request);
        $this->assertTrue($returnValue->hasBadge(TwoFactorCodeCredentials::class));
        $this->assertFalse($returnValue->hasBadge(RememberMeBadge::class));

        $credentials = $returnValue->getBadge(TwoFactorCodeCredentials::class);
        assert($credentials instanceof TwoFactorCodeCredentials);
        $this->assertEquals(self::CODE, $credentials->getCode());
    }

    /**
     * @test
     */
    public function authenticate_tokenHasRememberMeAttribute_createTwoFactorPassportWithRememberMeBadge(): void
    {
        $twoFactorToken = $this->stubTokenStorageHasTwoFactorToken();
        $twoFactorToken
            ->expects($this->any())
            ->method('hasAttribute')
            ->with(TwoFactorTokenInterface::ATTRIBUTE_NAME_USE_REMEMBER_ME)
            ->willReturn(true);

        $returnValue = $this->authenticator->authenticate($this->request);
        $this->assertTrue($returnValue->hasBadge(RememberMeBadge::class));
    }

    /**
     * @test
     */
    public function authenticate_csrfDisabled_noCsrfBadge(): void
    {
        $this->stubCsrfProtectionEnabled(false);
        $this->stubTokenStorageHasTwoFactorToken();

        $returnValue = $this->authenticator->authenticate($this->request);
        $this->assertFalse($returnValue->hasBadge(CsrfTokenBadge::class));
    }

    /**
     * @test
     */
    public function authenticate_csrfEnabled_csrfBadgeAdded(): void
    {
        $this->stubCsrfProtectionEnabled(true);
        $this->stubTokenStorageHasTwoFactorToken();

        $returnValue = $this->authenticator->authenticate($this->request);
        $this->assertTrue($returnValue->hasBadge(CsrfTokenBadge::class));

        $credentials = $returnValue->getBadge(CsrfTokenBadge::class);
        assert($credentials instanceof CsrfTokenBadge);
        $this->assertEquals(self::CSRF_TOKEN, $credentials->getCsrfToken());
        $this->assertEquals(self::CSRF_TOKEN_ID, $credentials->getCsrfTokenId());
    }

    /**
     * @test
     */
    public function authenticate_trustedDeviceParameterNotSet_noTrustedDeviceBadge(): void
    {
        $this->stubRequestHasTrustedDeviceParameter(false);
        $this->stubRememberMeSetsTrusted(false);
        $this->stubTokenStorageHasTwoFactorToken();

        $returnValue = $this->authenticator->authenticate($this->request);
        $this->assertFalse($returnValue->hasBadge(TrustedDeviceBadge::class));
    }

    /**
     * @test
     */
    public function authenticate_trustedDeviceParameterSet_addTrustedDeviceBadge(): void
    {
        $this->stubRequestHasTrustedDeviceParameter(true);
        $this->stubRememberMeSetsTrusted(false);
        $this->stubTokenStorageHasTwoFactorToken();

        $returnValue = $this->authenticator->authenticate($this->request);
        $this->assertTrue($returnValue->hasBadge(TrustedDeviceBadge::class));
    }

    /**
     * @test
     */
    public function authenticate_rememberMeSetsTrustedWithRememberMeEnabled_addTrustedDeviceBadge(): void
    {
        $this->stubRequestHasTrustedDeviceParameter(false);
        $this->stubRememberMeSetsTrusted(true);
        $twoFactorToken = $this->stubTokenStorageHasTwoFactorToken();
        $twoFactorToken
            ->expects($this->any())
            ->method('hasAttribute')
            ->with(TwoFactorTokenInterface::ATTRIBUTE_NAME_USE_REMEMBER_ME)
            ->willReturn(true);

        $returnValue = $this->authenticator->authenticate($this->request);
        $this->assertTrue($returnValue->hasBadge(TrustedDeviceBadge::class));
    }

    /**
     * @test
     */
    public function createToken_multiFactorAuthenticationNotComplete_returnTwoFactorToken(): void
    {
        $this->stubIsMultiFactorFirewall(true);
        $authenticatedToken = $this->createMock(TokenInterface::class);
        $twoFactorToken = $this->createTwoFactorToken($authenticatedToken, false);
        $passport = $this->createPassportWithTwoFactorCredentials($twoFactorToken);

        $returnValue = $this->authenticator->createToken($passport, self::FIREWALL_NAME);
        $this->assertSame($twoFactorToken, $returnValue);
    }

    /**
     * @test
     */
    public function createToken_multiFactorAuthenticationIsComplete_returnAuthenticatedToken(): void
    {
        $this->stubIsMultiFactorFirewall(true);
        $authenticatedToken = $this->createMock(TokenInterface::class);
        $twoFactorToken = $this->createTwoFactorToken($authenticatedToken, true);
        $passport = $this->createPassportWithTwoFactorCredentials($twoFactorToken);

        $this->expect2faCompleteFlagSet($authenticatedToken);

        $returnValue = $this->authenticator->createToken($passport, self::FIREWALL_NAME);
        $this->assertSame($authenticatedToken, $returnValue);
    }

    /**
     * @test
     */
    public function createToken_noMultiFactorAuthentication_returnAuthenticatedToken(): void
    {
        $this->stubIsMultiFactorFirewall(false);
        $authenticatedToken = $this->createMock(TokenInterface::class);
        $twoFactorToken = $this->createTwoFactorToken($authenticatedToken, false);
        $passport = $this->createPassportWithTwoFactorCredentials($twoFactorToken);

        $this->expect2faCompleteFlagSet($authenticatedToken);

        $returnValue = $this->authenticator->createToken($passport, self::FIREWALL_NAME);
        $this->assertSame($authenticatedToken, $returnValue);
    }

    /**
     * @test
     */
    public function onAuthenticationSuccess_authenticationIncomplete_dispatchSuccessAndRequireEvent(): void
    {
        $this->expectDispatchEvents([TwoFactorAuthenticationEvents::SUCCESS, TwoFactorAuthenticationEvents::REQUIRE]);

        $this->authenticator->onAuthenticationSuccess($this->request, $this->createTwoFactorToken(), self::FIREWALL_NAME);
    }

    /**
     * @test
     */
    public function onAuthenticationSuccess_authenticationIncomplete_returnRequireHandlerResult(): void
    {
        $token = $this->createTwoFactorToken();
        $response = $this->createMock(Response::class);

        $this->authenticationRequiredHandler
            ->expects($this->once())
            ->method('onAuthenticationRequired')
            ->with($this->request, $token)
            ->willReturn($response);

        $returnValue = $this->authenticator->onAuthenticationSuccess($this->request, $token, self::FIREWALL_NAME);
        $this->assertSame($response, $returnValue);
    }

    /**
     * @test
     */
    public function onAuthenticationSuccess_authenticationComplete_dispatchSuccessAndCompleteEvent(): void
    {
        $this->expectDispatchEvents([TwoFactorAuthenticationEvents::SUCCESS, TwoFactorAuthenticationEvents::COMPLETE]);

        $this->authenticator->onAuthenticationSuccess($this->request, $this->createMock(TokenInterface::class), self::FIREWALL_NAME);
    }

    /**
     * @test
     */
    public function onAuthenticationSuccess_authenticationComplete_returnSuccessHandlerResponse(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $response = $this->createMock(Response::class);

        $this->successHandler
            ->expects($this->once())
            ->method('onAuthenticationSuccess')
            ->with($this->request, $token)
            ->willReturn($response);

        $returnValue = $this->authenticator->onAuthenticationSuccess($this->request, $token, self::FIREWALL_NAME);
        $this->assertSame($response, $returnValue);
    }

    /**
     * @test
     */
    public function onAuthenticationFailure_exceptionGiven_dispatchFailureEvent(): void
    {
        $this->stubTokenStorageHasToken($this->createMock(TokenInterface::class));

        $this->expectDispatchEvents([TwoFactorAuthenticationEvents::FAILURE]);

        $this->authenticator->onAuthenticationFailure($this->request, $this->createMock(AuthenticationException::class));
    }

    /**
     * @test
     */
    public function onAuthenticationFailure_exceptionGiven_returnFailureHandlerResponse(): void
    {
        $this->stubTokenStorageHasToken($this->createMock(TokenInterface::class));
        $failureException = $this->createMock(AuthenticationException::class);
        $response = $this->createMock(Response::class);

        $this->failureHandler
            ->expects($this->once())
            ->method('onAuthenticationFailure')
            ->with($this->request, $failureException)
            ->willReturn($response);

        $returnValue = $this->authenticator->onAuthenticationFailure($this->request, $failureException);
        $this->assertSame($response, $returnValue);
    }
}
