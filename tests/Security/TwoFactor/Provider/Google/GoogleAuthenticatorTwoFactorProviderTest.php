<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Provider\Google;

use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Exception\TwoFactorProviderLogicException;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorTwoFactorProvider;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorFormRendererInterface;
use Scheb\TwoFactorBundle\Tests\TestCase;
use stdClass;
use Symfony\Component\Security\Core\User\UserInterface;

class GoogleAuthenticatorTwoFactorProviderTest extends TestCase
{
    private const SECRET = 'SECRET';

    private MockObject|GoogleAuthenticatorInterface $authenticator;
    private GoogleAuthenticatorTwoFactorProvider $provider;

    protected function setUp(): void
    {
        $this->authenticator = $this->createMock(GoogleAuthenticatorInterface::class);
        $formRenderer = $this->createMock(TwoFactorFormRendererInterface::class);
        $this->provider = new GoogleAuthenticatorTwoFactorProvider($this->authenticator, $formRenderer);
    }

    private function createUser(bool $enabled = true, ?string $secret = self::SECRET): MockObject|UserWithTwoFactorInterface
    {
        $user = $this->createMock(UserWithTwoFactorInterface::class);
        $user
            ->expects($this->any())
            ->method('isGoogleAuthenticatorEnabled')
            ->willReturn($enabled);
        $user
            ->expects($this->any())
            ->method('getGoogleAuthenticatorSecret')
            ->willReturn($secret);

        return $user;
    }

    private function createAuthenticationContext(?UserInterface $user = null): MockObject|AuthenticationContextInterface
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
    public function beginAuthentication_twoFactorEnabledHasSecret_returnTrue(): void
    {
        $user = $this->createUser(true);
        $context = $this->createAuthenticationContext($user);

        $returnValue = $this->provider->beginAuthentication($context);
        $this->assertTrue($returnValue);
    }

    /**
     * @test
     */
    public function beginAuthentication_twoFactorEnabledHasNoSecret_throwTwoFactorProviderLogicException(): void
    {
        $user = $this->createUser(true, '');
        $context = $this->createAuthenticationContext($user);

        $this->expectException(TwoFactorProviderLogicException::class);
        $this->provider->beginAuthentication($context);
    }

    /**
     * @test
     */
    public function beginAuthentication_twoFactorEnabledHasNullSecret_throwTwoFactorProviderLogicException(): void
    {
        $user = $this->createUser(true, null);
        $context = $this->createAuthenticationContext($user);

        $this->expectException(TwoFactorProviderLogicException::class);
        $this->provider->beginAuthentication($context);
    }

    /**
     * @test
     */
    public function beginAuthentication_twoFactorDisabledHasSecret_returnFalse(): void
    {
        $user = $this->createUser(false);
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
        $user = new stdClass();

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
    public function validateAuthenticationCode_codeGiven_returnValidationResult(bool $validationResult): void
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

    /**
     * @return array<array<bool>>
     */
    public function provideValidationResult(): array
    {
        return [
            [true],
            [false],
        ];
    }
}
