<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Controller;

use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Controller\FormController;
use Scheb\TwoFactorBundle\Security\Authentication\Exception\TwoFactorProviderNotFoundException;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorFormRendererInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderRegistry;
use Scheb\TwoFactorBundle\Security\TwoFactor\TwoFactorFirewallConfig;
use Scheb\TwoFactorBundle\Security\TwoFactor\TwoFactorFirewallContext;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Logout\LogoutUrlGenerator;

class FormControllerTest extends TestCase
{
    private const CURRENT_TWO_FACTOR_PROVIDER = 'provider1';
    private const AUTH_CODE_PARAM_NAME = 'auth_code_param_name';
    private const TRUSTED_PARAM_NAME = 'trusted_param_name';
    private const FIREWALL_NAME = 'firewallName';
    private const CSRF_PARAMETER = 'csrf_parameter';
    private const CSRF_TOKEN_ID = 'csrf_token_id';
    private const LOGOUT_PATH = '/logout';

    /**
     * @var MockObject|TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var MockObject|TwoFactorProviderRegistry
     */
    private $providerRegistry;

    /**
     * @var MockObject|SessionInterface
     */
    private $session;

    /**
     * @var MockObject|Request
     */
    private $request;

    /**
     * @var MockObject|TwoFactorFormRendererInterface
     */
    private $formRenderer;

    /**
     * @var MockObject|TwoFactorTokenInterface
     */
    private $twoFactorToken;

    /**
     * @var MockObject|TwoFactorFirewallConfig
     */
    private $firewallConfig;

    /**
     * @var MockObject|TwoFactorFirewallContext
     */
    private $twoFactorFirewallContext;

    /**
     * @var MockObject|LogoutUrlGenerator
     */
    private $logoutUrlGenerator;

    /**
     * @var FormController
     */
    private $controller;

    protected function setUp(): void
    {
        $this->session = $this->createMock(SessionInterface::class);
        $this->request = $this->createMock(Request::class);
        $this->request
            ->expects($this->any())
            ->method('getSession')
            ->willReturn($this->session);

        $this->formRenderer = $this->createMock(TwoFactorFormRendererInterface::class);
        $twoFactorProvider = $this->createMock(TwoFactorProviderInterface::class);
        $twoFactorProvider
            ->expects($this->any())
            ->method('getFormRenderer')
            ->willReturn($this->formRenderer);

        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);

        $this->providerRegistry = $this->createMock(TwoFactorProviderRegistry::class);
        $this->providerRegistry
            ->expects($this->any())
            ->method('getProvider')
            ->with(self::CURRENT_TWO_FACTOR_PROVIDER)
            ->willReturn($twoFactorProvider);

        $this->firewallConfig = $this->createMock(TwoFactorFirewallConfig::class);
        $this->firewallConfig
            ->expects($this->any())
            ->method('getAuthCodeParameterName')
            ->willReturn(self::AUTH_CODE_PARAM_NAME);
        $this->firewallConfig
            ->expects($this->any())
            ->method('getTrustedParameterName')
            ->willReturn(self::TRUSTED_PARAM_NAME);
        $this->firewallConfig
            ->expects($this->any())
            ->method('getCsrfParameterName')
            ->willReturn(self::CSRF_PARAMETER);
        $this->firewallConfig
            ->expects($this->any())
            ->method('getCsrfTokenId')
            ->willReturn(self::CSRF_TOKEN_ID);

        $this->twoFactorFirewallContext = $this->createMock(TwoFactorFirewallContext::class);
        $this->twoFactorFirewallContext
            ->expects($this->any())
            ->method('getFirewallConfig')
            ->with(self::FIREWALL_NAME)
            ->willReturn($this->firewallConfig);

        $this->logoutUrlGenerator = $this->createMock(LogoutUrlGenerator::class);
        $this->logoutUrlGenerator
            ->expects($this->any())
            ->method('getLogoutPath')
            ->willReturn(self::LOGOUT_PATH);

        $this->initControllerWithTrustedFeature(true);
    }

    private function initControllerWithTrustedFeature(bool $trustedFeature): void
    {
        $this->controller = new FormController($this->tokenStorage, $this->providerRegistry, $this->twoFactorFirewallContext, $this->logoutUrlGenerator, $trustedFeature);
    }

    private function stubFirewallIsMultiFactor(bool $isMultiFactor): void
    {
        $this->firewallConfig
            ->expects($this->any())
            ->method('isMultiFactor')
            ->willReturn($isMultiFactor);
    }

    private function stubTokenStorageHasToken(TokenInterface $token): void
    {
        $this->tokenStorage
            ->expects($this->any())
            ->method('getToken')
            ->willReturn($token);
    }

    private function stubTokenStorageHasTwoFactorToken(array $providers = ['provider1', 'provider2']): void
    {
        $this->twoFactorToken = $this->createMock(TwoFactorTokenInterface::class);
        $this->twoFactorToken
            ->expects($this->any())
            ->method('getCurrentTwoFactorProvider')
            ->willReturn(self::CURRENT_TWO_FACTOR_PROVIDER);

        $this->twoFactorToken
            ->expects($this->any())
            ->method('getTwoFactorProviders')
            ->willReturn($providers);

        $this->twoFactorToken
            ->expects($this->any())
            ->method('getProviderKey')
            ->willReturn(self::FIREWALL_NAME);

        $this->stubTokenStorageHasToken($this->twoFactorToken);
    }

    private function stubRequestParameters(array $params): void
    {
        $this->request
            ->expects($this->any())
            ->method('get')
            ->willReturnCallback(function (string $paramName) use ($params) {
                return $params[$paramName] ?? null;
            });
    }

    private function stubSessionHasException(\Exception $exception): void
    {
        $this->session
            ->expects($this->any())
            ->method('get')
            ->with(Security::AUTHENTICATION_ERROR)
            ->willReturn($exception);
    }

    private function stubFirewallIsCsrfProtected(): void
    {
        $this->firewallConfig
            ->expects($this->any())
            ->method('isCsrfProtectionEnabled')
            ->willReturn(true);
    }

    private function assertTemplateVars(callable $callback): void
    {
        $this->formRenderer
            ->expects($this->once())
            ->method('renderForm')
            ->with($this->anything(), $this->callback($callback));
    }

    private function assertTemplateVarsHaveAuthenticationError($error, $errorData): void
    {
        $this->assertTemplateVars(function (array $templateVars) use ($error, $errorData) {
            $this->assertArrayHasKey('authenticationError', $templateVars);
            $this->assertArrayHasKey('authenticationErrorData', $templateVars);

            $this->assertEquals($error, $templateVars['authenticationError']);
            $this->assertEquals($errorData, $templateVars['authenticationErrorData']);

            return true;
        });
    }

    /**
     * @test
     */
    public function form_noTwoFactorToken_throwAccessDeniedException(): void
    {
        $this->stubTokenStorageHasToken($this->createMock(TokenInterface::class));

        $this->expectException(AccessDeniedException::class);
        $this->controller->form($this->request);
    }

    /**
     * @test
     */
    public function form_setPreferredProvider_switchCurrentProvider(): void
    {
        $this->stubTokenStorageHasTwoFactorToken();
        $this->stubRequestParameters(['preferProvider' => 'provider2']);

        $this->twoFactorToken
            ->expects($this->once())
            ->method('preferTwoFactorProvider')
            ->with('provider2');

        $this->controller->form($this->request);
    }

    /**
     * @test
     */
    public function form_hasAuthenticationError_passErrorToRenderer(): void
    {
        $exception = new TwoFactorProviderNotFoundException('Authentication exception message');
        $exception->setProvider('unknownProvider');

        $this->stubTokenStorageHasTwoFactorToken();
        $this->stubSessionHasException($exception);

        $this->assertTemplateVarsHaveAuthenticationError(
            TwoFactorProviderNotFoundException::MESSAGE_KEY,
            ['{{ provider }}' => 'unknownProvider']
        );

        $this->controller->form($this->request);
    }

    /**
     * @test
     */
    public function form_hasOtherError_notPassErrorToRenderer(): void
    {
        $this->stubTokenStorageHasTwoFactorToken();
        $this->stubSessionHasException(new \Exception('Exception message'));

        $this->assertTemplateVarsHaveAuthenticationError(null, null);

        $this->controller->form($this->request);
    }

    /**
     * @test
     */
    public function form_multiFactorFirewallTwoProviders_displayTrustedOptionFalse(): void
    {
        $this->stubFirewallIsMultiFactor(true);
        $this->stubTokenStorageHasTwoFactorToken(['provider1', 'provider2']);

        $this->assertTemplateVars(function (array $templateVars) {
            $this->assertArrayHasKey('displayTrustedOption', $templateVars);
            $this->assertFalse($templateVars['displayTrustedOption']);

            return true;
        });

        $this->controller->form($this->request);
    }

    /**
     * @test
     */
    public function form_multiFactorFirewallOneProviderLeft_displayTrustedOptionTrue(): void
    {
        $this->stubFirewallIsMultiFactor(true);
        $this->stubTokenStorageHasTwoFactorToken(['provider1']);

        $this->assertTemplateVars(function (array $templateVars) {
            $this->assertArrayHasKey('displayTrustedOption', $templateVars);
            $this->assertTrue($templateVars['displayTrustedOption']);

            return true;
        });

        $this->controller->form($this->request);
    }

    /**
     * @test
     */
    public function form_notMultiFactorFirewallTwoProviders_displayTrustedOptionTrue(): void
    {
        $this->stubFirewallIsMultiFactor(false);
        $this->stubTokenStorageHasTwoFactorToken(['provider1', 'provider2']);

        $this->assertTemplateVars(function (array $templateVars) {
            $this->assertArrayHasKey('displayTrustedOption', $templateVars);
            $this->assertTrue($templateVars['displayTrustedOption']);

            return true;
        });

        $this->controller->form($this->request);
    }

    /**
     * @test
     */
    public function form_trustedDisabledMultiFactorFirewallOneProviderLeft_displayTrustedOptionFalse(): void
    {
        $this->initControllerWithTrustedFeature(false);
        $this->stubFirewallIsMultiFactor(true);
        $this->stubTokenStorageHasTwoFactorToken(['provider1']);

        $this->assertTemplateVars(function (array $templateVars) {
            $this->assertArrayHasKey('displayTrustedOption', $templateVars);
            $this->assertFalse($templateVars['displayTrustedOption']);

            return true;
        });

        $this->controller->form($this->request);
    }

    /**
     * @test
     */
    public function form_trustedDisabledNotMultiFactorFirewallTwoProviders_displayTrustedOptionFalse(): void
    {
        $this->initControllerWithTrustedFeature(false);
        $this->stubFirewallIsMultiFactor(false);
        $this->stubTokenStorageHasTwoFactorToken(['provider1', 'provider2']);

        $this->assertTemplateVars(function (array $templateVars) {
            $this->assertArrayHasKey('displayTrustedOption', $templateVars);
            $this->assertFalse($templateVars['displayTrustedOption']);

            return true;
        });

        $this->controller->form($this->request);
    }

    /**
     * @test
     */
    public function form_csrfTokenGeneratorInstanceOfCsrfTokenManagerInterface_isCsrfProtectionEnabledTrue(): void
    {
        $this->stubTokenStorageHasTwoFactorToken();
        $this->stubFirewallIsCsrfProtected();

        $this->assertTemplateVars(function (array $templateVars) {
            $this->assertArrayHasKey('isCsrfProtectionEnabled', $templateVars);
            $this->assertTrue($templateVars['isCsrfProtectionEnabled']);

            return true;
        });

        $this->controller->form($this->request);
    }

    /**
     * @test
     */
    public function form_renderForm_renderTemplateWithTemplateVars(): void
    {
        $this->firewallConfig
            ->expects($this->any())
            ->method('getCheckPath')
            ->willReturn('/2fa_check');

        $this->stubTokenStorageHasTwoFactorToken();

        $this->assertTemplateVars(function (array $templateVars) {
            $this->assertArrayHasKey('twoFactorProvider', $templateVars);
            $this->assertArrayHasKey('availableTwoFactorProviders', $templateVars);
            $this->assertArrayHasKey('authenticationError', $templateVars);
            $this->assertArrayHasKey('authenticationErrorData', $templateVars);
            $this->assertArrayHasKey('displayTrustedOption', $templateVars);
            $this->assertArrayHasKey('authCodeParameterName', $templateVars);
            $this->assertArrayHasKey('trustedParameterName', $templateVars);
            $this->assertArrayHasKey('isCsrfProtectionEnabled', $templateVars);
            $this->assertArrayHasKey('csrfParameterName', $templateVars);
            $this->assertArrayHasKey('csrfTokenId', $templateVars);
            $this->assertArrayHasKey('checkPathRoute', $templateVars);
            $this->assertArrayHasKey('checkPathUrl', $templateVars);
            $this->assertArrayHasKey('logoutPath', $templateVars);

            $this->assertEquals(self::CURRENT_TWO_FACTOR_PROVIDER, $templateVars['twoFactorProvider']);
            $this->assertEquals(['provider1', 'provider2'], $templateVars['availableTwoFactorProviders']);
            $this->assertEquals(self::AUTH_CODE_PARAM_NAME, $templateVars['authCodeParameterName']);
            $this->assertEquals(self::TRUSTED_PARAM_NAME, $templateVars['trustedParameterName']);
            $this->assertFalse($templateVars['isCsrfProtectionEnabled']);
            $this->assertEquals(self::CSRF_PARAMETER, $templateVars['csrfParameterName']);
            $this->assertEquals(self::CSRF_TOKEN_ID, $templateVars['csrfTokenId']);
            $this->assertEquals(self::LOGOUT_PATH, $templateVars['logoutPath']);
            $this->assertEquals('/2fa_check', $templateVars['checkPathUrl']);
            $this->assertNull($templateVars['checkPathRoute']);

            return true;
        });

        $this->controller->form($this->request);
    }

    /**
     * @test
     */
    public function form_renderForm_renderTemplateWithTemplateVarsSetsRoutePath(): void
    {
        $this->firewallConfig
            ->expects($this->any())
            ->method('getCheckPath')
            ->willReturn('admin_2fa_check');

        $this->stubTokenStorageHasTwoFactorToken();

        $this->assertTemplateVars(function (array $templateVars) {
            $this->assertArrayHasKey('twoFactorProvider', $templateVars);
            $this->assertArrayHasKey('availableTwoFactorProviders', $templateVars);
            $this->assertArrayHasKey('authenticationError', $templateVars);
            $this->assertArrayHasKey('authenticationErrorData', $templateVars);
            $this->assertArrayHasKey('displayTrustedOption', $templateVars);
            $this->assertArrayHasKey('authCodeParameterName', $templateVars);
            $this->assertArrayHasKey('trustedParameterName', $templateVars);
            $this->assertArrayHasKey('isCsrfProtectionEnabled', $templateVars);
            $this->assertArrayHasKey('csrfParameterName', $templateVars);
            $this->assertArrayHasKey('csrfTokenId', $templateVars);
            $this->assertArrayHasKey('checkPathRoute', $templateVars);
            $this->assertArrayHasKey('checkPathUrl', $templateVars);
            $this->assertArrayHasKey('logoutPath', $templateVars);

            $this->assertEquals(self::CURRENT_TWO_FACTOR_PROVIDER, $templateVars['twoFactorProvider']);
            $this->assertEquals(['provider1', 'provider2'], $templateVars['availableTwoFactorProviders']);
            $this->assertEquals(self::AUTH_CODE_PARAM_NAME, $templateVars['authCodeParameterName']);
            $this->assertEquals(self::TRUSTED_PARAM_NAME, $templateVars['trustedParameterName']);
            $this->assertFalse($templateVars['isCsrfProtectionEnabled']);
            $this->assertEquals(self::CSRF_PARAMETER, $templateVars['csrfParameterName']);
            $this->assertEquals(self::CSRF_TOKEN_ID, $templateVars['csrfTokenId']);
            $this->assertEquals(self::LOGOUT_PATH, $templateVars['logoutPath']);
            $this->assertEquals('admin_2fa_check', $templateVars['checkPathRoute']);
            $this->assertNull($templateVars['checkPathUrl']);

            return true;
        });

        $this->controller->form($this->request);
    }
}
