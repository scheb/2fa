<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\IpWhitelist;

use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\IpWhitelist\DefaultIpWhitelistProvider;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class DefaultIpWhitelistProviderTest extends TestCase
{
    /**
     * @var MockObject|Request
     */
    private $request;

    /**
     * @var MockObject|TokenInterface
     */
    private $token;

    protected function setUp(): void
    {
        $this->request = $this->createMock(Request::class);
        $this->token = $this->createMock(TokenInterface::class);
    }

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
