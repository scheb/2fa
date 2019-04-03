<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor;

use Scheb\TwoFactorBundle\Security\TwoFactor\TwoFactorFirewallConfig;
use Scheb\TwoFactorBundle\Security\TwoFactor\TwoFactorFirewallContext;
use Scheb\TwoFactorBundle\Tests\TestCase;

class TwoFactorFirewallContextTest extends TestCase
{
    /**
     * @var TwoFactorFirewallContext
     */
    private $firewallContext;

    protected function setUp(): void
    {
        $firewallConfig = new TwoFactorFirewallConfig(['foo' => 'bar']);
        $this->firewallContext = new TwoFactorFirewallContext(['firewallName' => $firewallConfig]);
    }

    /**
     * @test
     */
    public function getFirewallConfig_isRegistered_returnFirewallConfig(): void
    {
        $returnValue = $this->firewallContext->getFirewallConfig('firewallName');
        $this->assertInstanceOf(TwoFactorFirewallConfig::class, $returnValue);
    }

    /**
     * @test
     */
    public function getFirewallConfig_unknownFirewall_throwInvalidArgumentException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->firewallContext->getFirewallConfig('unknownFirewallName');
    }
}
