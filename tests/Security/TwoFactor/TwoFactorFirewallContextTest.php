<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Scheb\TwoFactorBundle\Security\TwoFactor\TwoFactorFirewallConfig;
use Scheb\TwoFactorBundle\Security\TwoFactor\TwoFactorFirewallContext;
use Scheb\TwoFactorBundle\Tests\TestCase;

class TwoFactorFirewallContextTest extends TestCase
{
    private const string FIREWALL_NAME = 'firewallName';

    private TwoFactorFirewallContext $firewallContext;

    protected function setUp(): void
    {
        $firewallConfig = $this->createMock(TwoFactorFirewallConfig::class);
        $this->firewallContext = new TwoFactorFirewallContext([self::FIREWALL_NAME => $firewallConfig]);
    }

    #[Test]
    public function getFirewallConfig_isRegistered_returnFirewallConfig(): void
    {
        $returnValue = $this->firewallContext->getFirewallConfig(self::FIREWALL_NAME);
        $this->assertInstanceOf(TwoFactorFirewallConfig::class, $returnValue);
    }

    #[Test]
    public function getFirewallConfig_unknownFirewall_throwInvalidArgumentException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->firewallContext->getFirewallConfig('unknownFirewallName');
    }
}
