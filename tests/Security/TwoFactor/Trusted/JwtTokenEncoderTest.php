<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Trusted;

use DateTimeImmutable;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Plain;
use Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\JwtTokenEncoder;
use Scheb\TwoFactorBundle\Tests\TestCase;
use function base64_decode;
use function base64_encode;
use function sprintf;

class JwtTokenEncoderTest extends TestCase
{
    private const CLAIM = 'test';
    private const TOKEN_ID = 'tokenId';
    private const APPLICATION_SECRET = 'oW2+MyKdgOf+iS1+qqgqb0FK5+1oZjDYGBemymEuzgU=';

    private JwtTokenEncoder $encoder;
    private Configuration $configuration;

    protected function setUp(): void
    {
        $this->encoder = new JwtTokenEncoder(base64_decode(self::APPLICATION_SECRET));
        $this->configuration = Configuration::forSymmetricSigner(new Sha256(), InMemory::base64Encoded(self::APPLICATION_SECRET));
    }

    protected function createToken(DateTimeImmutable $expirationDate): string
    {
        return $this->configuration->builder()
            ->withClaim(self::CLAIM, self::TOKEN_ID)
            ->expiresAt($expirationDate)
            ->getToken($this->configuration->signer(), $this->configuration->signingKey())
            ->toString();
    }

    protected function assertJwtClaim(Plain $jwtToken, string $name, mixed $expectedValue): void
    {
        $this->assertEquals($expectedValue, $jwtToken->claims()->get($name, false));
    }

    /**
     * @test
     */
    public function generateToken_withClaims_returnEncodedToken(): void
    {
        $jwtToken = $this->encoder->generateToken('username', 'firewallName', 1, new DateTimeImmutable());
        $this->assertInstanceOf(Plain::class, $jwtToken);
        $this->assertJwtClaim($jwtToken, JwtTokenEncoder::CLAIM_USERNAME, 'username');
        $this->assertJwtClaim($jwtToken, JwtTokenEncoder::CLAIM_FIREWALL, 'firewallName');
        $this->assertJwtClaim($jwtToken, JwtTokenEncoder::CLAIM_VERSION, 1);
        $this->assertFalse($jwtToken->isExpired(new DateTimeImmutable('-100 seconds')));
        $this->assertTrue($jwtToken->isExpired(new DateTimeImmutable('+100 seconds')));
    }

    /**
     * @test
     */
    public function decodeToken_invalidToken_returnNull(): void
    {
        $decodedToken = $this->encoder->decodeToken('invalidToken');
        $this->assertNull($decodedToken);
    }

    /**
     * @test
     */
    public function decodeToken_expiredToken_returnNull(): void
    {
        $encodedToken = $this->createToken(new DateTimeImmutable('-1000 seconds'));
        $decodedToken = $this->encoder->decodeToken($encodedToken);
        $this->assertNull($decodedToken);
    }

    /**
     * @test
     */
    public function decodeToken_validToken_returnDecodedToken(): void
    {
        $encodedToken = $this->createToken(new DateTimeImmutable('+1000 seconds'));
        $decodedToken = $this->encoder->decodeToken($encodedToken);
        $this->assertInstanceOf(Plain::class, $decodedToken);
        $this->assertJwtClaim($decodedToken, self::CLAIM, self::TOKEN_ID);
    }

    /**
     * @test
     */
    public function decodeToken_validAlgAndSignature_returnDecodedToken(): void
    {
        $encodedToken = sprintf(
            '%s.%s.%s',
            base64_encode('{"typ":"JWT","alg":"HS256"}'),
            'eyJ0ZXN0IjoidG9rZW5JZCJ9',
            'sQft2vmMyZ1kL1FPLN5vsg0akuyMoDMNjP9adFxnYOs'
        );

        $this->assertInstanceOf(Plain::class, $this->encoder->decodeToken($encodedToken));
    }

    /**
     * @test
     */
    public function decodeToken_ignoredAlgNone_returnNull(): void
    {
        $encodedNoneAlgToken = sprintf(
            '%s.%s.%s',
            base64_encode('{"typ":"JWT","alg":"none"}'), // Modified the algorithm from 'HS256' to 'none'
            'eyJ0ZXN0IjoidG9rZW5JZCJ9',
            'sQft2vmMyZ1kL1FPLN5vsg0akuyMoDMNjP9adFxnYOs'
        );

        $this->assertNull($this->encoder->decodeToken($encodedNoneAlgToken));
    }

    /**
     * @test
     */
    public function decodeToken_ignoredAlgTest_returnNull(): void
    {
        $encodedTestAlgToken = sprintf(
            '%s.%s.%s',
            base64_encode('{"typ":"JWT","alg":"test"}'), // Modified the algorithm from 'HS256' to 'test'
            'eyJ0ZXN0IjoidG9rZW5JZCJ9',
            'sQft2vmMyZ1kL1FPLN5vsg0akuyMoDMNjP9adFxnYOs'
        );

        $this->assertNull($this->encoder->decodeToken($encodedTestAlgToken));
    }

    /**
     * @test
     */
    public function decodeToken_validAlgWrongSignature_returnNull(): void
    {
        $encodedInvalidSignatureToken = sprintf(
            '%s.%s.%s',
            base64_encode('{"typ":"JWT","alg":"HS256"}'),
            'eyJ0ZXN0IjoidG9rZW5JZCJ9',
            'invalid'
        );

        $this->assertNull($this->encoder->decodeToken($encodedInvalidSignatureToken));
    }
}
