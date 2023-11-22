<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Provider\Totp;

use OTPHP\TOTP;
use OTPHP\TOTPInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticator;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpFactory;
use Scheb\TwoFactorBundle\Tests\TestCase;
use function strlen;

class TotpAuthenticatorTest extends TestCase
{
    private MockObject|TwoFactorInterface $user;
    private MockObject|TotpFactory $totpFactory;
    private MockObject|TOTP $totp;
    private TotpAuthenticator $authenticator;

    protected function setUp(): void
    {
        $this->user = $this->createMock(TwoFactorInterface::class);
        $this->totp = $this->createMock(TOTPInterface::class);

        $this->totpFactory = $this->createMock(TotpFactory::class);
        $this->totpFactory
            ->expects($this->any())
            ->method('createTotpForUser')
            ->with($this->user)
            ->willReturn($this->totp);

        $this->authenticator = new TotpAuthenticator($this->totpFactory, 123, 42);
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
    public function provideCheckCodeData(): array
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
        $this->authenticator = new TotpAuthenticator($this->totpFactory, 123, 42);

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
        $this->authenticator = new TotpAuthenticator($this->totpFactory, 123, null);

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
    public function getProvisioningUri_getContentForQrCode_returnUri(): void
    {
        $this->totp
            ->expects($this->once())
            ->method('getProvisioningUri')
            ->willReturn('provisioningUri');

        $returnValue = $this->authenticator->getQRContent($this->user);
        $this->assertEquals('provisioningUri', $returnValue);
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
