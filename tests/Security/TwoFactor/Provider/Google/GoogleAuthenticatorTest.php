<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Provider\Google;

use OTPHP\TOTP;
use OTPHP\TOTPInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticator;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleTotpFactory;
use Scheb\TwoFactorBundle\Tests\TestCase;

class GoogleAuthenticatorTest extends TestCase
{
    /**
     * @var MockObject|TwoFactorInterface
     */
    private $user;

    /**
     * @var MockObject|GoogleTotpFactory
     */
    private $totpFactory;

    /**
     * @var MockObject|TOTP
     */
    private $totp;

    /**
     * @var GoogleAuthenticator
     */
    private $authenticator;

    protected function setUp(): void
    {
        $this->user = $this->createMock(TwoFactorInterface::class);
        $this->totp = $this->createMock(TOTPInterface::class);

        $this->totpFactory = $this->createMock(GoogleTotpFactory::class);
        $this->totpFactory
            ->expects($this->any())
            ->method('createTotpForUser')
            ->with($this->user)
            ->willReturn($this->totp);

        $this->authenticator = new GoogleAuthenticator($this->totpFactory, 123);
    }

    /**
     * @test
     * @dataProvider getCheckCodeData
     */
    public function checkCode_validateCode_returnBoolean($code, $expectedReturnValue): void
    {
        $this->totp
            ->expects($this->once())
            ->method('verify')
            ->with($code)
            ->willReturn($expectedReturnValue);

        $returnValue = $this->authenticator->checkCode($this->user, $code);
        $this->assertEquals($expectedReturnValue, $returnValue);
    }

    public function getCheckCodeData(): array
    {
        return [
            ['validCode', true],
            ['invalidCode', false],
        ];
    }

    /**
     * @test
     */
    public function checkCode_codeWithSpaces_stripSpacesBeforeCheck(): void
    {
        $this->totp
            ->expects($this->once())
            ->method('verify')
            ->with('123456', null, 123)
            ->willReturn(true);

        $this->authenticator->checkCode($this->user, ' 123 456 ');
    }

    /**
     * @test
     */
    public function getQRContent_getContentForQrCode_returnUri(): void
    {
        $this->totp
            ->expects($this->once())
            ->method('getProvisioningUri')
            ->willReturn('QRCodeContent');

        $returnValue = $this->authenticator->getQRContent($this->user);
        $this->assertEquals('QRCodeContent', $returnValue);
    }

    /**
     * @test
     */
    public function generateSecret_getRandomSecretCode_returnString(): void
    {
        $returnValue = $this->authenticator->generateSecret();
        $this->assertEquals(52, strlen($returnValue));
    }
}
