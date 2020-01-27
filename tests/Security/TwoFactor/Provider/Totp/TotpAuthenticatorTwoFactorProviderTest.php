<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Provider\Totp;

use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfigurationInterface;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorTwoFactorProvider;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorFormRendererInterface;
use Scheb\TwoFactorBundle\Tests\TestCase;

class TotpAuthenticatorTwoFactorProviderTest extends TestCase
{
    /**
     * @var MockObject|TotpAuthenticatorInterface
     */
    private $authenticator;

    /**
     * @var TotpAuthenticatorTwoFactorProvider
     */
    private $provider;

    protected function setUp(): void
    {
        $this->authenticator = $this->createMock(TotpAuthenticatorInterface::class);
        $formRenderer = $this->createMock(TwoFactorFormRendererInterface::class);
        $this->provider = new TotpAuthenticatorTwoFactorProvider($this->authenticator, $formRenderer);
    }

    /**
     * @return MockObject|TwoFactorInterface
     */
    private function createUser(bool $enabled = true, bool $hasTotpConfiguration = true): MockObject
    {
        $user = $this->createMock(TwoFactorInterface::class);
        $user
            ->expects($this->any())
            ->method('isTotpAuthenticationEnabled')
            ->willReturn($enabled);

        $totpConfiguration = $hasTotpConfiguration ? $this->createMock(TotpConfigurationInterface::class) : null;
        $user
            ->expects($this->any())
            ->method('getTotpAuthenticationConfiguration')
            ->willReturn($totpConfiguration);

        return $user;
    }

    /**
     * @return MockObject|AuthenticationContextInterface
     */
    private function createAuthenticationContext($user = null): MockObject
    {
        $authContext = $this->createMock(AuthenticationContextInterface::class);
        $authContext
            ->expects($this->any())
            ->method('getUser')
            ->willReturn($user ? $user : $this->createUser());

        return $authContext;
    }

    /**
     * @test
     */
    public function beginAuthentication_twoFactorEnabledHasTotpConfiguration_returnTrue(): void
    {
        $user = $this->createUser(true, true);
        $context = $this->createAuthenticationContext($user);

        $returnValue = $this->provider->beginAuthentication($context);
        $this->assertTrue($returnValue);
    }

    /**
     * @test
     */
    public function beginAuthentication_twoFactorEnabledNoTotpConfiguration_returnFalse(): void
    {
        $user = $this->createUser(true, false);
        $context = $this->createAuthenticationContext($user);

        $returnValue = $this->provider->beginAuthentication($context);
        $this->assertFalse($returnValue);
    }

    /**
     * @test
     */
    public function beginAuthentication_twoFactorDisabledHasTotpConfiguration_returnFalse(): void
    {
        $user = $this->createUser(false, true);
        $context = $this->createAuthenticationContext($user);

        $returnValue = $this->provider->beginAuthentication($context);
        $this->assertFalse($returnValue);
    }

    /**
     * @test
     */
    public function beginAuthentication_interfaceNotImplemented_returnFalse(): void
    {
        $user = new \stdClass(); //Any class without TwoFactorInterface
        $context = $this->createAuthenticationContext($user);

        $returnValue = $this->provider->beginAuthentication($context);
        $this->assertFalse($returnValue);
    }

    /**
     * @test
     */
    public function validateAuthenticationCode_noTwoFactorUser_returnFalse(): void
    {
        $user = new \stdClass();

        $this->authenticator
            ->expects($this->never())
            ->method($this->anything());

        $returnValue = $this->provider->validateAuthenticationCode($user, 'code');
        $this->assertFalse($returnValue);
    }

    /**
     * @test
     * @dataProvider provideValidationResult
     */
    public function validateAuthenticationCode_codeGiven_returnValidationResult($validationResult): void
    {
        $user = $this->createUser();

        $this->authenticator
            ->expects($this->once())
            ->method('checkCode')
            ->with($user, 'code')
            ->willReturn($validationResult);

        $returnValue = $this->provider->validateAuthenticationCode($user, 'code');
        $this->assertEquals($validationResult, $returnValue);
    }

    public function provideValidationResult(): array
    {
        return [
            [true],
            [false],
        ];
    }
}
