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
use function strlen;

class GoogleAuthenticatorTest extends TestCase
{
    private MockObject|TwoFactorInterface $user;
    private MockObject|GoogleTotpFactory $totpFactory;
    private MockObject|TOTP $totp;
    private GoogleAuthenticator $authenticator;

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

        $this->authenticator = new GoogleAuthenticator($this->totpFactory, 123, 42);
    }

    /**
     * @test
     * @dataProvider provideCheckCodeData
     */
    public function checkCode_validateCode_returnBoolean(string $code, bool $expectedReturnValue): void
    {
        $this->totp
            ->expects($this->once())
            ->method('verify')
            ->with($code)
            ->willReturn($expectedReturnValue);

        $returnValue = $this->authenticator->checkCode($this->user, $code);
        $this->assertEquals($expectedReturnValue, $returnValue);
    }

    /**
     * @return array<array<mixed>>
     */
    public static function provideCheckCodeData(): array
    {
        return [
            ['validCode', true],
            ['invalidCode', false],
        ];
    }

    /**
     * @test
     */
    public function checkCode_leewayGiven_leewayValueUsed(): void
    {
        $this->authenticator = new GoogleAuthenticator($this->totpFactory, 123, 42);

        $this->totp
            ->expects($this->once())
            ->method('verify')
            ->with('code', null, 42);

        $this->authenticator->checkCode($this->user, 'code');
    }

    /**
     * @test
     */
    public function checkCode_onlyWindowValueGiven_windowValueUsed(): void
    {
        $this->authenticator = new GoogleAuthenticator($this->totpFactory, 123, null);

        $this->totp
            ->expects($this->once())
            ->method('verify')
            ->with('code', null, 123);

        $this->authenticator->checkCode($this->user, 'code');
    }

    /**
     * @test
     */
    public function checkCode_codeWithSpaces_stripSpacesBeforeCheck(): void
    {
        $this->totp
            ->expects($this->once())
            ->method('verify')
            ->with('123456', null, 42)
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
