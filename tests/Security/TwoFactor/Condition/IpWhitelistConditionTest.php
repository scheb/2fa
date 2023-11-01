<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Condition;

use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\TwoFactor\Condition\IpWhitelistCondition;
use Scheb\TwoFactorBundle\Security\TwoFactor\IpWhitelist\IpWhitelistProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class IpWhitelistConditionTest extends AbstractAuthenticationContextTestCase
{
    private IpWhitelistCondition $ipWhitelistHandler;

    protected function setUp(): void
    {
        $ipWhitelist = [
            '127.0.0.1',
            '192.168.0.0/16',
            '2001:0db8:85a3:0000:0000:8a2e:0370:7334',
            '2001:db8:abcd:0012::0/64',
        ];

        $ipWhitelistProvider = $this->createMock(IpWhitelistProviderInterface::class);
        $ipWhitelistProvider
            ->expects($this->any())
            ->method('getWhitelistedIps')
            ->willReturn($ipWhitelist);

        $this->ipWhitelistHandler = new IpWhitelistCondition($ipWhitelistProvider);
    }

    private function createRequestWithIp(string $ip): MockObject|Request
    {
        $request = $this->createRequest();
        $request
            ->expects($this->once())
            ->method('getClientIp')
            ->willReturn($ip);

        return $request;
    }

    /**
     * @test
     * @dataProvider provideWhitelistedIps
     */
    public function shouldPerformTwoFactorAuthentication_ipIsWhitelisted_returnFalse(string $ip): void
    {
        $request = $this->createRequestWithIp($ip);
        $originalToken = $this->createToken();
        $authenticationContext = $this->createAuthenticationContext($request, $originalToken);

        $returnValue = $this->ipWhitelistHandler->shouldPerformTwoFactorAuthentication($authenticationContext);
        $this->assertFalse($returnValue);
    }

    /**
     * @return string[][]
     */
    public static function provideWhitelistedIps(): array
    {
        return [
            ['127.0.0.1'],
            ['192.168.0.1'],
            ['2001:0db8:85a3:0000:0000:8a2e:0370:7334'],
            ['2001:db8:abcd:0012:0000:0000:0000:0001'],
        ];
    }

    /**
     * @test
     */
    public function shouldPerformTwoFactorAuthentication_ipNotWhitelisted_returnTrue(): void
    {
        $request = $this->createRequestWithIp('1.1.1.1');
        $transformedToken = $this->createToken();
        $authenticationContext = $this->createAuthenticationContext($request);

        $returnValue = $this->ipWhitelistHandler->shouldPerformTwoFactorAuthentication($authenticationContext);
        $this->assertTrue($returnValue);
    }
}
