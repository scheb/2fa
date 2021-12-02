<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor;

use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\Http\Utils\RequestDataReader;
use Scheb\TwoFactorBundle\Security\TwoFactor\TwoFactorFirewallConfig;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\HttpUtils;

class TwoFactorFirewallConfigTest extends TestCase
{
    private const FIREWALL_NAME = 'firewallName';
    private const FULL_OPTIONS = [
        'check_path' => 'check_path_route_name',
        'post_only' => false,
        'auth_form_path' => 'auth_form_path_route_name',
        'multi_factor' => true,
        'auth_code_parameter_name' => 'auth_code_param',
        'trusted_parameter_name' => 'trusted_param',
        'remember_me_sets_trusted' => true,
        'enable_csrf' => true,
        'csrf_parameter' => 'parameter_name',
        'csrf_token_id' => 'token_id',
    ];

    /**
     * @var MockObject|HttpUtils
     */
    private $httpUtils;

    /**
     * @var MockObject|RequestDataReader
     */
    private $requestDataReader;

    protected function setUp(): void
    {
        $this->httpUtils = $this->createMock(HttpUtils::class);
        $this->requestDataReader = $this->createMock(RequestDataReader::class);
    }

    private function createConfig($options = self::FULL_OPTIONS): TwoFactorFirewallConfig
    {
        return new TwoFactorFirewallConfig(
            $options,
            self::FIREWALL_NAME,
            $this->httpUtils,
            $this->requestDataReader
        );
    }

    private function stubRequestMethod(MockObject|Request $request, string $method): void
    {
        $request
            ->expects($this->any())
            ->method('isMethod')
            ->willReturnCallback(function (string $arg) use ($method) {
                return $arg === $method;
            });
    }

    private function stubCheckRequestPath(MockObject|Request $request, string $pathToCheck, bool $result): void
    {
        $this->httpUtils
            ->expects($this->any())
            ->method('checkRequestPath')
            ->with($request, $pathToCheck)
            ->willReturn($result);
    }

    /**
     * @test
     */
    public function getFirewallName_isSet_returnFirewallName(): void
    {
        $this->assertEquals(self::FIREWALL_NAME, $this->createConfig()->getFirewallName());
    }

    /**
     * @test
     */
    public function isMultiFactor_optionSet_returnThatValue(): void
    {
        $returnValue = $this->createConfig()->isMultiFactor();
        $this->assertTrue($returnValue);
    }

    /**
     * @test
     */
    public function getAuthCodeParameterName_optionSet_returnThatValue(): void
    {
        $returnValue = $this->createConfig()->getAuthCodeParameterName();
        $this->assertEquals('auth_code_param', $returnValue);
    }

    /**
     * @test
     */
    public function getTrustedParameterName_optionSet_returnThatValue(): void
    {
        $returnValue = $this->createConfig()->getTrustedParameterName();
        $this->assertEquals('trusted_param', $returnValue);
    }

    /**
     * @test
     */
    public function isRememberMeSetsTrusted_optionIsNotSet_returnFalse(): void
    {
        $returnValue = $this->createConfig([])->isRememberMeSetsTrusted();
        $this->assertFalse($returnValue);
    }

    /**
     * @test
     */
    public function isRememberMeSetsTrusted_optionDisabled_returnTrue(): void
    {
        $returnValue = $this->createConfig(['remember_me_sets_trusted' => false])->isRememberMeSetsTrusted();
        $this->assertFalse($returnValue);
    }

    /**
     * @test
     */
    public function isRememberMeSetsTrusted_optionEnabled_returnTrue(): void
    {
        $returnValue = $this->createConfig(self::FULL_OPTIONS)->isRememberMeSetsTrusted();
        $this->assertTrue($returnValue);
    }

    /**
     * @test
     */
    public function getCsrfParameterName_optionSet_returnThatValue(): void
    {
        $returnValue = $this->createConfig()->getCsrfParameterName();
        $this->assertEquals('parameter_name', $returnValue);
    }

    /**
     * @test
     */
    public function getCsrfTokenId_optionSet_returnThatValue(): void
    {
        $returnValue = $this->createConfig()->getCsrfTokenId();
        $this->assertEquals('token_id', $returnValue);
    }

    /**
     * @test
     */
    public function isCsrfProtectionEnabled_csrfOptionIsNotSet_returnFalse(): void
    {
        $returnValue = $this->createConfig([])->isCsrfProtectionEnabled();
        $this->assertFalse($returnValue);
    }

    /**
     * @test
     */
    public function isCsrfProtectionEnabled_csrfDisabled_returnTrue(): void
    {
        $returnValue = $this->createConfig(['enable_csrf' => false])->isCsrfProtectionEnabled();
        $this->assertFalse($returnValue);
    }

    /**
     * @test
     */
    public function isCsrfProtectionEnabled_csrfEnabled_returnTrue(): void
    {
        $returnValue = $this->createConfig(self::FULL_OPTIONS)->isCsrfProtectionEnabled();
        $this->assertTrue($returnValue);
    }

    /**
     * @test
     */
    public function getAuthFormPath_optionSet_returnThatValue(): void
    {
        $returnValue = $this->createConfig()->getAuthFormPath();
        $this->assertEquals('auth_form_path_route_name', $returnValue);
    }

    /**
     * @test
     */
    public function getAuthFormPath_optionNotSet_returnDefault(): void
    {
        $returnValue = $this->createConfig([])->getAuthFormPath();
        $this->assertEquals('/2fa', $returnValue);
    }

    /**
     * @test
     */
    public function getCheckPath_optionSet_returnThatValue(): void
    {
        $returnValue = $this->createConfig()->getCheckPath();
        $this->assertEquals('check_path_route_name', $returnValue);
    }

    /**
     * @test
     */
    public function getCheckPath_optionNotSet_returnDefault(): void
    {
        $returnValue = $this->createConfig([])->getCheckPath();
        $this->assertEquals('/2fa_check', $returnValue);
    }

    /**
     * @test
     */
    public function isPostOnly_optionSet_returnThatValue(): void
    {
        $returnValue = $this->createConfig()->isPostOnly();
        $this->assertFalse($returnValue);
    }

    /**
     * @test
     */
    public function isPostOnly_optionNotSet_returnDefault(): void
    {
        $returnValue = $this->createConfig([])->isPostOnly();
        $this->assertTrue($returnValue);
    }

    /**
     * @test
     */
    public function isCheckPathRequest_pathNotMatches_returnFalse(): void
    {
        $request = $this->createMock(Request::class);
        $this->stubRequestMethod($request, 'POST');
        $this->stubcheckRequestPath($request, '/check_path', false);

        $config = $this->createConfig([
            'check_path' => '/check_path',
            'post_only' => true,
        ]);
        $this->assertFalse($config->isCheckPathRequest($request));
    }

    /**
     * @test
     */
    public function isCheckPathRequest_pathMatchesButWrongMethod_returnFalse(): void
    {
        $request = $this->createMock(Request::class);
        $this->stubRequestMethod($request, 'GET');
        $this->stubcheckRequestPath($request, '/check_path', true);

        $config = $this->createConfig([
            'check_path' => '/check_path',
            'post_only' => true,
        ]);
        $this->assertFalse($config->isCheckPathRequest($request));
    }

    /**
     * @test
     */
    public function isCheckPathRequest_pathMatchesAndCorrectMethod_returnTrue(): void
    {
        $request = $this->createMock(Request::class);
        $this->stubRequestMethod($request, 'POST');
        $this->stubcheckRequestPath($request, '/check_path', true);

        $config = $this->createConfig([
            'check_path' => '/check_path',
            'post_only' => true,
        ]);

        $this->assertTrue($config->isCheckPathRequest($request));
    }

    /**
     * @test
     */
    public function isAuthFormRequest_pathMatches_returnTrue(): void
    {
        $request = $this->createMock(Request::class);
        $this->stubcheckRequestPath($request, '/auth_form_path', true);
        $config = $this->createConfig(['auth_form_path' => '/auth_form_path']);

        $this->assertTrue($config->isAuthFormRequest($request));
    }

    /**
     * @test
     */
    public function isAuthFormRequest_differentPath_returnFalse(): void
    {
        $request = $this->createMock(Request::class);
        $this->stubcheckRequestPath($request, '/auth_form_path', false);
        $config = $this->createConfig(['auth_form_path' => '/auth_form_path']);

        $this->assertFalse($config->isAuthFormRequest($request));
    }

    /**
     * @test
     */
    public function getAuthCodeFromRequest_parameterConfigured_returnRequestData(): void
    {
        $request = $this->createMock(Request::class);
        $config = $this->createConfig(['auth_code_parameter_name' => 'auth_code']);

        $this->requestDataReader
            ->expects($this->once())
            ->method('getRequestValue')
            ->with($this->identicalTo($request), 'auth_code')
            ->willReturn('authCodeValue');

        $returnValue = $config->getAuthCodeFromRequest($request);
        $this->assertEquals('authCodeValue', $returnValue);
    }

    /**
     * @test
     */
    public function hasTrustedDeviceParameterInRequest_trueLikeValue_returnTrue(): void
    {
        $request = $this->createMock(Request::class);
        $config = $this->createConfig(['trusted_parameter_name' => 'trusted_flag']);

        $this->requestDataReader
            ->expects($this->once())
            ->method('getRequestValue')
            ->with($this->identicalTo($request), 'trusted_flag')
            ->willReturn(1);

        $returnValue = $config->hasTrustedDeviceParameterInRequest($request);
        $this->assertTrue($returnValue);
    }

    /**
     * @test
     */
    public function hasTrustedDeviceParameterInRequest_falseLikeValue_returnFalse(): void
    {
        $request = $this->createMock(Request::class);
        $config = $this->createConfig(['trusted_parameter_name' => 'trusted_flag']);

        $this->requestDataReader
            ->expects($this->once())
            ->method('getRequestValue')
            ->with($this->identicalTo($request), 'trusted_flag')
            ->willReturn(0);

        $returnValue = $config->hasTrustedDeviceParameterInRequest($request);
        $this->assertFalse($returnValue);
    }

    /**
     * @test
     */
    public function getCsrfTokenFromRequest_hasParameter_returnValue(): void
    {
        $request = $this->createMock(Request::class);
        $config = $this->createConfig(['csrf_parameter' => 'csrf_code']);

        $this->requestDataReader
            ->expects($this->once())
            ->method('getRequestValue')
            ->with($this->identicalTo($request), 'csrf_code')
            ->willReturn('csrfCodeValue');

        $returnValue = $config->getCsrfTokenFromRequest($request);
        $this->assertEquals('csrfCodeValue', $returnValue);
    }
}
