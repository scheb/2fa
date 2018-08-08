<?php

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor;

use Scheb\TwoFactorBundle\Security\TwoFactor\TwoFactorFirewallConfig;
use Scheb\TwoFactorBundle\Tests\TestCase;

class TwoFactorFirewallConfigTest extends TestCase
{
    /**
     * @var TwoFactorFirewallConfig
     */
    private $config;

    protected function setUp()
    {
        $this->config = new TwoFactorFirewallConfig([
            'multi_factor' => true,
            'auth_code_parameter_name' => 'auth_code_param',
            'trusted_parameter_name' => 'trusted_param',
            'csrf_parameter_name' => 'parameter_name',
            'csrf_token_id' => 'token_id',
        ]);
    }

    /**
     * @test
     */
    public function isMultiFactor_optionSet_returnThatValue()
    {
        $returnValue = $this->config->isMultiFactor();
        $this->assertTrue($returnValue);
    }

    /**
     * @test
     */
    public function getAuthCodeParameterName_optionSet_returnThatValue()
    {
        $returnValue = $this->config->getAuthCodeParameterName();
        $this->assertEquals('auth_code_param', $returnValue);
    }

    /**
     * @test
     */
    public function getTrustedParameterName_optionSet_returnThatValue()
    {
        $returnValue = $this->config->getTrustedParameterName();
        $this->assertEquals('trusted_param', $returnValue);
    }

    /**
     * @test
     */
    public function getCsrfParameterName_optionSet_returnThatValue()
    {
        $returnValue = $this->config->getCsrfParameterName();
        $this->assertEquals('parameter_name', $returnValue);
    }

    /**
     * @test
     */
    public function getCsrfTokenId_optionSet_returnThatValue()
    {
        $returnValue = $this->config->getCsrfTokenId();
        $this->assertEquals('token_id', $returnValue);
    }
}
