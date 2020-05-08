<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor;

use Scheb\TwoFactorBundle\Security\TwoFactor\TwoFactorFirewallConfig;
use Scheb\TwoFactorBundle\Tests\TestCase;

class TwoFactorFirewallConfigTest extends TestCase
{
    private const FULL_OPTIONS = [
        'check_path' => 'check_path_route_name',
        'post_only' => true,
        'auth_form_path' => 'auth_form_path_route_name',
        'multi_factor' => true,
        'auth_code_parameter_name' => 'auth_code_param',
        'trusted_parameter_name' => 'trusted_param',
        'csrf_parameter' => 'parameter_name',
        'csrf_token_id' => 'token_id',
        'csrf_token_generator' => 'csrf_token_generator',
    ];

    private function createConfig($options = self::FULL_OPTIONS): TwoFactorFirewallConfig
    {
        return new TwoFactorFirewallConfig($options);
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
    public function isCsrfProtectionEnabled_configuredCsrfTokenGeneratorIsNull_returnFalse(): void
    {
        $returnValue = $this->createConfig([])->isCsrfProtectionEnabled();
        $this->assertFalse($returnValue);
    }

    /**
     * @test
     */
    public function isCsrfProtectionEnabled_configuredCsrfTokenGeneratorIsString_returnTrue(): void
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
        $this->assertTrue($returnValue);
    }

    /**
     * @test
     */
    public function isPostOnly_optionNotSet_returnDefault(): void
    {
        $returnValue = $this->createConfig([])->isPostOnly();
        $this->assertFalse($returnValue);
    }
}
