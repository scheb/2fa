<?php

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\IpWhitelist;

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
        $provider = new DefaultIpWhitelistProvider($ipWhitelist);
        $this->assertEquals($ipWhitelist, $provider->getWhitelistedIps());
    }
}
