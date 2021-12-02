<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\IpWhitelist;

use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\IpWhitelist\DefaultIpWhitelistProvider;
use Scheb\TwoFactorBundle\Tests\TestCase;

class DefaultIpWhitelistProviderTest extends TestCase
{
    /**
     * @test
     */
    public function testGetWhitelistedIps_hasIpsConfigured_returnThoseIps(): void
    {
        $ipWhitelist = ['1.0.0.0', '2.0.0.0'];
        $context = $this->createMock(AuthenticationContextInterface::class);
        $provider = new DefaultIpWhitelistProvider($ipWhitelist);
        $this->assertEquals($ipWhitelist, $provider->getWhitelistedIps($context));
    }
}
