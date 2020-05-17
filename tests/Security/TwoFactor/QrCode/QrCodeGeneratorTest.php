<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\QrCode;

use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface as GoogleAuthenticatorTwoFactorInterface;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface as TotpTwoFactorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\QrCode\QrCodeGenerator;
use Scheb\TwoFactorBundle\Tests\TestCase;

class QrCodeGeneratorTest extends TestCase
{
    /**
     * @test
     */
    public function getGoogleAuthenticatorQrCode_featureDisabled_throwException(): void
    {
        $user = $this->createMock(GoogleAuthenticatorTwoFactorInterface::class);
        $generator = new QrCodeGenerator(null, null);

        $this->expectException(\RuntimeException::class);

        $generator->getGoogleAuthenticatorQrCode($user);
    }

    /**
     * @test
     */
    public function getGoogleAuthenticatorQrCode_featureEnabled_returnQrCode(): void
    {
        $googleAuthenticator = $this->createMock(GoogleAuthenticatorInterface::class);
        $user = $this->createMock(GoogleAuthenticatorTwoFactorInterface::class);
        $generator = new QrCodeGenerator($googleAuthenticator, null);

        $googleAuthenticator
            ->expects($this->any())
            ->method('getQRContent')
            ->with($user)
            ->willReturn('content');

        $returnValue = $generator->getGoogleAuthenticatorQrCode($user);
        $this->assertEquals('content', $returnValue->getText());
    }

    /**
     * @test
     */
    public function getTotpQrCode_featureDisabled_throwException(): void
    {
        $user = $this->createMock(TotpTwoFactorInterface::class);
        $generator = new QrCodeGenerator(null, null);

        $this->expectException(\RuntimeException::class);

        $generator->getTotpQrCode($user);
    }

    /**
     * @test
     */
    public function getTotpQrCode_featureEnabled_returnQrCode(): void
    {
        $totpAuthenticator = $this->createMock(TotpAuthenticatorInterface::class);
        $user = $this->createMock(TotpTwoFactorInterface::class);
        $generator = new QrCodeGenerator(null, $totpAuthenticator);

        $totpAuthenticator
            ->expects($this->any())
            ->method('getQRContent')
            ->with($user)
            ->willReturn('content');

        $returnValue = $generator->getTotpQrCode($user);
        $this->assertEquals('content', $returnValue->getText());
    }
}
