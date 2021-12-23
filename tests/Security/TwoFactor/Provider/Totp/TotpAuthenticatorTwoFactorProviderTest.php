<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Provider\Totp;

use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfigurationInterface;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Exception\TwoFactorProviderLogicException;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorTwoFactorProvider;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorFormRendererInterface;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;

class TotpAuthenticatorTwoFactorProviderTest extends TestCase
{
    private const SECRET = 'SECRET';

    private MockObject|TotpAuthenticatorInterface $authenticator;
    private TotpAuthenticatorTwoFactorProvider $provider;

    protected function setUp(): void
    {
        $this->authenticator = $this->createMock(TotpAuthenticatorInterface::class);
        $formRenderer = $this->createMock(TwoFactorFormRendererInterface::class);
        $this->provider = new TotpAuthenticatorTwoFactorProvider($this->authenticator, $formRenderer);
    }

    private function createUser(bool $enabled = true, bool $hasTotpConfiguration = true, ?string $secret = self::SECRET): MockObject|TwoFactorInterface
    {
        $user = $this->createMock(UserWithTwoFactorInterface::class);
        $user
            ->expects($this->any())
            ->method('isTotpAuthenticationEnabled')
            ->willReturn($enabled);

        $totpConfiguration = null;
        if ($hasTotpConfiguration) {
            $totpConfiguration = $this->createMock(TotpConfigurationInterface::class);
            $totpConfiguration
                ->expects($this->any())
                ->method('getSecret')
                ->willReturn($secret);
        }

        $user
            ->expects($this->any())
            ->method('getTotpAuthenticationConfiguration')
            ->willReturn($totpConfiguration);

        return $user;
    }

    private function createAuthenticationContext($user = null): MockObject|AuthenticationContextInterface
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
    public function beginAuthentication_twoFactorEnabledNoTotpConfiguration_throwTwoFactorProviderLogicException(): void
    {
        $user = $this->createUser(true, false);
        $context = $this->createAuthenticationContext($user);

        $this->expectException(TwoFactorProviderLogicException::class);
        $this->provider->beginAuthentication($context);
    }

    /**
     * @test
     */
    public function beginAuthentication_twoFactorEnabledHasNoSecret_throwTwoFactorProviderLogicException(): void
    {
        $user = $this->createUser(true, true, '');
        $context = $this->createAuthenticationContext($user);

        $this->expectException(TwoFactorProviderLogicException::class);
        $this->provider->beginAuthentication($context);
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
        $user = $this->createMock(UserInterface::class);
        $context = $this->createAuthenticationContext($user);

        $returnValue = $this->provider->beginAuthentication($context);
        $this->assertFalse($returnValue);
    }

    /**
     * @test
     */
    public function validateAuthenticationCode_noTwoFactorUser_returnFalse(): void
    {
        $user = $this->createMock(UserInterface::class);

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

// Used to mock combined interfaces
interface UserWithTwoFactorInterface extends UserInterface, TwoFactorInterface
{
}
