<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Provider\Google;

use OTPHP\TOTP;
use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Exception\TwoFactorProviderLogicException;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleTotpFactory;
use Scheb\TwoFactorBundle\Tests\TestCase;

class GoogleTotpFactoryTest extends TestCase
{
    private const USER_NAME = 'User Name';
    private const SECRET = 'SECRET';
    private const ISSUER = 'Issuer Name';
    private const SERVER = 'Server';
    private const CUSTOM_DIGITS = 8;
    private const DEFAULT_DIGITS = 6;

    private function createUserMock(?string $secret = self::SECRET): MockObject|TwoFactorInterface
    {
        $user = $this->createMock(TwoFactorInterface::class);
        $user
            ->expects($this->any())
            ->method('getGoogleAuthenticatorUsername')
            ->willReturn(self::USER_NAME);
        $user
            ->expects($this->any())
            ->method('getGoogleAuthenticatorSecret')
            ->willReturn($secret);

        return $user;
    }

    /**
     * @test
     */
    public function createTotpForUser_missingSecretCode_throwTwoFactorProviderLogicException(): void
    {
        $user = $this->createUserMock('');

        $this->expectException(TwoFactorProviderLogicException::class);
        (new GoogleTotpFactory(self::SERVER, self::ISSUER, self::CUSTOM_DIGITS))->createTotpForUser($user);
    }

    /**
     * @test
     */
    public function createTotpForUser_nullSecretCode_throwTwoFactorProviderLogicException(): void
    {
        $user = $this->createUserMock(null);

        $this->expectException(TwoFactorProviderLogicException::class);
        (new GoogleTotpFactory(self::SERVER, self::ISSUER, self::CUSTOM_DIGITS))->createTotpForUser($user);
    }

    /**
     * @test
     */
    public function createTotpForUser_factoryCalled_returnTotpObject(): void
    {
        $user = $this->createUserMock();
        $returnValue = (new GoogleTotpFactory(self::SERVER, self::ISSUER, self::CUSTOM_DIGITS))->createTotpForUser($user);

        $this->assertInstanceOf(TOTP::class, $returnValue);
        $this->assertEquals(self::CUSTOM_DIGITS, $returnValue->getDigits());
        $this->assertEquals(self::USER_NAME.'@'.self::SERVER, $returnValue->getLabel());
        $this->assertEquals(self::ISSUER, $returnValue->getIssuer());
        $this->assertEquals(self::SECRET, $returnValue->getSecret());
    }

    /**
     * @test
     * @dataProvider provideHostnameAndIssuer
     */
    public function getProvisioningUri_hostnameAndIssuerGiven_returnProvisioningUri(?string $hostname, ?string $issuer, int $digits, string $expectedUrl): void
    {
        $user = $this->createUserMock();
        $totp = (new GoogleTotpFactory($hostname, $issuer, $digits))->createTotpForUser($user);

        $returnValue = $totp->getProvisioningUri();
        $this->assertEquals($expectedUrl, $returnValue);
    }

    /**
     * @return array<array<mixed>>
     */
    public static function provideHostnameAndIssuer(): array
    {
        return [
            [null, null, self::DEFAULT_DIGITS, 'otpauth://totp/User%20Name?secret=SECRET'],
            [self::SERVER, null, self::DEFAULT_DIGITS, 'otpauth://totp/User%20Name%40Server?secret=SECRET'],
            [null, self::ISSUER, self::DEFAULT_DIGITS, 'otpauth://totp/Issuer%20Name%3AUser%20Name?issuer=Issuer%20Name&secret=SECRET'],
            [self::SERVER, self::ISSUER, self::DEFAULT_DIGITS, 'otpauth://totp/Issuer%20Name%3AUser%20Name%40Server?issuer=Issuer%20Name&secret=SECRET'],
            [self::SERVER, self::ISSUER, self::CUSTOM_DIGITS, 'otpauth://totp/Issuer%20Name%3AUser%20Name%40Server?digits=8&issuer=Issuer%20Name&secret=SECRET'],
        ];
    }
}
