<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Provider\Totp;

use OTPHP\TOTP;
use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfiguration;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpFactory;
use Scheb\TwoFactorBundle\Tests\TestCase;

class TotpFactoryTest extends TestCase
{
    private const ISSUER = 'Issuer Name';
    private const SERVER = 'Server Name';

    private const CUSTOM_PARAMETER_NAME = 'image';
    private const CUSTOM_PARAMETER_VALUE = 'logo.png';
    private const CUSTOM_PARAMETERS = [self::CUSTOM_PARAMETER_NAME => self::CUSTOM_PARAMETER_VALUE];

    private const USER_NAME = 'User Name';
    private const SECRET = 'SECRET';
    private const PERIOD = 20;
    private const DIGITS = 8;
    private const ALGORITHM = TotpConfiguration::ALGORITHM_SHA256;

    /**
     * @return MockObject|TwoFactorInterface
     */
    private function createUserMock(): MockObject
    {
        $config = new TotpConfiguration(self::SECRET, self::ALGORITHM, self::PERIOD, self::DIGITS);

        $user = $this->createMock(TwoFactorInterface::class);
        $user
            ->expects($this->once())
            ->method('getTotpAuthenticationUsername')
            ->willReturn(self::USER_NAME);
        $user
            ->expects($this->once())
            ->method('getTotpAuthenticationConfiguration')
            ->willReturn($config);

        return $user;
    }

    /**
     * @test
     */
    public function createTotpForUser_factoryCalled_returnTotpObject(): void
    {
        $user = $this->createUserMock();
        $returnValue = (new TotpFactory(self::SERVER, self::ISSUER, self::CUSTOM_PARAMETERS))->createTotpForUser($user);

        $this->assertInstanceOf(TOTP::class, $returnValue);
        $this->assertEquals(self::SECRET, $returnValue->getSecret());
        $this->assertEquals(self::ALGORITHM, $returnValue->getDigest());
        $this->assertEquals(self::PERIOD, $returnValue->getPeriod());
        $this->assertEquals(self::DIGITS, $returnValue->getDigits());

        $this->assertEquals(self::USER_NAME.'@'.self::SERVER, $returnValue->getLabel());
        $this->assertEquals(self::ISSUER, $returnValue->getIssuer());
        $this->assertEquals(self::CUSTOM_PARAMETER_VALUE, $returnValue->getParameter(self::CUSTOM_PARAMETER_NAME));
    }

    /**
     * @test
     * @dataProvider getHostnameAndIssuerToTest
     */
    public function getProvisioningUri_hostnameAndIssuerGiven_returnProvisioningUri(?string $hostname, ?string $issuer, array $customParameters, string $expectedUrl): void
    {
        $user = $this->createUserMock();
        $totp = (new TotpFactory($hostname, $issuer, $customParameters))->createTotpForUser($user);

        $returnValue = $totp->getProvisioningUri();
        $this->assertEquals($expectedUrl, $returnValue);
    }

    public function getHostnameAndIssuerToTest(): array
    {
        return [
            [null, null, [], 'otpauth://totp/User%20Name?algorithm=sha256&digits=8&period=20&secret=SECRET'],
            [self::SERVER, null, [], 'otpauth://totp/User%20Name%40Server%20Name?algorithm=sha256&digits=8&period=20&secret=SECRET'],
            [null, self::ISSUER, [], 'otpauth://totp/Issuer%20Name%3AUser%20Name?algorithm=sha256&digits=8&issuer=Issuer%20Name&period=20&secret=SECRET'],
            [null, null, self::CUSTOM_PARAMETERS, 'otpauth://totp/User%20Name?algorithm=sha256&digits=8&image=logo.png&period=20&secret=SECRET'],
            [self::SERVER, self::ISSUER, [], 'otpauth://totp/Issuer%20Name%3AUser%20Name%40Server%20Name?algorithm=sha256&digits=8&issuer=Issuer%20Name&period=20&secret=SECRET'],
            [self::SERVER, self::ISSUER, self::CUSTOM_PARAMETERS, 'otpauth://totp/Issuer%20Name%3AUser%20Name%40Server%20Name?algorithm=sha256&digits=8&image=logo.png&issuer=Issuer%20Name&period=20&secret=SECRET'],
        ];
    }
}
