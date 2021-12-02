<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Trusted;

use Lcobucci\JWT\Token\DataSet;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Token\Signature;
use Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\JwtTokenEncoder;
use Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\TrustedDeviceToken;
use Scheb\TwoFactorBundle\Tests\TestCase;

class TrustedDeviceTokenTest extends TestCase
{
    private TrustedDeviceToken $trustedToken;

    protected function setUp(): void
    {
        $claims = new DataSet([
            JwtTokenEncoder::CLAIM_USERNAME => 'username',
            JwtTokenEncoder::CLAIM_FIREWALL => 'firewallName',
            JwtTokenEncoder::CLAIM_VERSION => 1,
        ], 'encodedClaims');
        $jwtToken = new Plain(new DataSet([], 'encodedHeaders'), $claims, Signature::fromEmptyData());
        $this->trustedToken = new TrustedDeviceToken($jwtToken);
    }

    /**
     * @test
     */
    public function authenticatesRealm_usernameAndFirewallNameMatches_returnTrue(): void
    {
        $returnValue = $this->trustedToken->authenticatesRealm('username', 'firewallName');
        $this->assertTrue($returnValue);
    }

    /**
     * @test
     * @dataProvider provideWrongUsernameFirewallNameCombination
     */
    public function authenticatesRealm_usernameAndFirewallNameDiffernt_returnFalse(string $username, string $firewallName): void
    {
        $returnValue = $this->trustedToken->authenticatesRealm($username, $firewallName);
        $this->assertFalse($returnValue);
    }

    public function provideWrongUsernameFirewallNameCombination(): array
    {
        return [
            ['wrongUsername', 'firewallName'],
            ['username', 'wrongFirewallName'],
        ];
    }

    /**
     * @test
     */
    public function versionMatches_sameVersion_returnTrue(): void
    {
        $returnValue = $this->trustedToken->versionMatches(1);
        $this->assertTrue($returnValue);
    }

    /**
     * @test
     */
    public function versionMatches_differentVersion_returnFalse(): void
    {
        $returnValue = $this->trustedToken->versionMatches(2);
        $this->assertFalse($returnValue);
    }

    /**
     * @test
     */
    public function serialize_encodeToken_returnEncodedString(): void
    {
        $returnValue = $this->trustedToken->serialize();
        $this->assertEquals('encodedHeaders.encodedClaims.', $returnValue);
    }
}
