<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Trusted;

use Lcobucci\JWT\Token;
use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\JwtTokenEncoder;
use Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\TrustedDeviceToken;
use Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\TrustedDeviceTokenEncoder;
use Scheb\TwoFactorBundle\Tests\TestCase;

class TrustedDeviceTokenEncoderTest extends TestCase
{
    /**
     * @var MockObject|JwtTokenEncoder
     */
    private $jwtEncoder;

    /**
     * @var TestableTrustedDeviceTokenEncoder
     */
    private $tokenEncoder;

    protected function setUp(): void
    {
        $this->jwtEncoder = $this->createMock(JwtTokenEncoder::class);
        $this->tokenEncoder = new TestableTrustedDeviceTokenEncoder($this->jwtEncoder, 3600);
        $this->tokenEncoder->now = new \DateTimeImmutable('2018-01-01 00:00:00');
    }

    /**
     * @test
     */
    public function generateToken_parametersGiven_returnTrustedDeviceToken(): void
    {
        $this->jwtEncoder
            ->expects($this->once())
            ->method('generateToken')
            ->with('username', 'firewallName', 1, new \DateTime('2018-01-01 01:00:00'));

        $token = $this->tokenEncoder->generateToken('username', 'firewallName', 1);
        $this->assertInstanceOf(TrustedDeviceToken::class, $token);
    }

    /**
     * @test
     */
    public function decodeToken_validToken_returnDecodedTrustedDeviceToken(): void
    {
        $this->jwtEncoder
            ->expects($this->once())
            ->method('decodeToken')
            ->willReturn($this->createMock(Token::class));

        $returnValue = $this->tokenEncoder->decodeToken('validToken');
        $this->assertInstanceOf(TrustedDeviceToken::class, $returnValue);
    }

    /**
     * @test
     */
    public function decodeToken_invalidToken_returnNull(): void
    {
        $this->jwtEncoder
            ->expects($this->once())
            ->method('decodeToken')
            ->willReturn(null);

        $returnValue = $this->tokenEncoder->decodeToken('invalidToken');
        $this->assertNull($returnValue);
    }
}

// Make the current DateTime testable
class TestableTrustedDeviceTokenEncoder extends TrustedDeviceTokenEncoder
{
    public $now;

    protected function getDateTimeNow(): \DateTimeImmutable
    {
        return $this->now;
    }
}
