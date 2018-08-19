<?php

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Provider\Totp;

use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticator;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpFactory;
use Scheb\TwoFactorBundle\Tests\TestCase;

class TotpAuthenticatorTest extends TestCase
{
    private function createFactory(?string $issuer = null, int $period = 30, int $digits = 6, string $digest = 'sha1', array $customParameters = []): TotpFactory
    {
        return new TotpFactory(
            $issuer,
            $period,
            $digits,
            $digest,
            $customParameters
        );
    }

    private function createAuthenticator(TotpFactory $totpFactory): TotpAuthenticator
    {
        return new TotpAuthenticator(
            $totpFactory,
            'https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl={PROVISIONING_URI}',
            '{PROVISIONING_URI}'
        );
    }

    /**
     * @test
     * @dataProvider getHostnameAndIssuerToTest
     */
    public function getUrl_createQrCodeUrl_returnUrl(?string $issuer, string $provisioningUri, string $expectedUrl)
    {
        //Mock the user object
        $user = $this->createMock(TwoFactorInterface::class);
        $user
            ->expects($this->once())
            ->method('getTotpAuthenticationProvisioningUri')
            ->willReturn($provisioningUri);

        $factory = $this->createFactory($issuer);
        $totp = $factory->getTotpForUser($user);

        $authenticator = $this->createAuthenticator($factory);
        $returnValue = $authenticator->getUrl($totp);
        $this->assertEquals($expectedUrl, $returnValue);

        $this->assertEquals($provisioningUri, $totp->getProvisioningUri());
    }

    public function getHostnameAndIssuerToTest()
    {
        return [
            [null, 'otpauth://totp/User%20name?secret=SECRET', 'https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=otpauth%3A%2F%2Ftotp%2FUser%2520name%3Fsecret%3DSECRET'],
            [null, 'otpauth://totp/User%20name?secret=SECRET', 'https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=otpauth%3A%2F%2Ftotp%2FUser%2520name%3Fsecret%3DSECRET'],
            ['Issuer Name', 'otpauth://totp/Issuer%20Name%3AUser%20name?issuer=Issuer%20Name&secret=SECRET', 'https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=otpauth%3A%2F%2Ftotp%2FIssuer%2520Name%253AUser%2520name%3Fissuer%3DIssuer%2520Name%26secret%3DSECRET'],
            ['Issuer Name', 'otpauth://totp/Issuer%20Name%3AUser%20name?issuer=Issuer%20Name&secret=SECRET', 'https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=otpauth%3A%2F%2Ftotp%2FIssuer%2520Name%253AUser%2520name%3Fissuer%3DIssuer%2520Name%26secret%3DSECRET'],
        ];
    }
}
