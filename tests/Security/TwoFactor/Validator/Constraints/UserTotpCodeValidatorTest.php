<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Validator\Constraints;

use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfigurationInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Validator\Constraints\UserTotpCode;
use Scheb\TwoFactorBundle\Security\TwoFactor\Validator\Constraints\UserTotpCodeValidator;
use Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Provider\Totp\UserWithTwoFactorInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @implements ConstraintValidatorTestCase<UserTotpCodeValidator>
 */
class UserTotpCodeValidatorTest extends ConstraintValidatorTestCase
{
    private TokenStorageInterface $tokenStorage;
    private TotpAuthenticatorInterface&MockObject $totpAuthenticator;

    protected function createValidator(): UserTotpCodeValidator
    {
        return new UserTotpCodeValidator($this->tokenStorage, $this->totpAuthenticator);
    }

    protected function setUp(): void
    {
        $user = $this->createUser();
        $this->tokenStorage = $this->createTokenStorage($user);
        $this->totpAuthenticator = $this->createTotpAuthenticator();

        parent::setUp();
    }

    #[Test]
    #[DataProvider('provideConstraints')]
    public function validate_validCode_noViolation(UserTotpCode $constraint): void
    {
        $this->totpAuthenticator
            ->expects($this->any())
            ->method('checkCode')
            ->willReturn(true);

        $this->validator->validate('secret', $constraint);

        $this->assertNoViolation();
    }

    #[Test]
    #[DataProvider('provideConstraints')]
    public function validate_invalidCode_raisedViolation(UserTotpCode $constraint): void
    {
        $this->totpAuthenticator
            ->expects($this->any())
            ->method('checkCode')
            ->willReturn(false);

        $this->validator->validate('secret', $constraint);

        $this->buildViolation('myMessage')
            ->setCode(UserTotpCode::INVALID_TOTP_CODE_ERROR)
            ->setTranslationDomain('myDomain')
            ->assertRaised();
    }

    /**
     * @return Generator<string, list{UserTotpCode}>
     */
    public static function provideConstraints(): iterable
    {
        // Test backwards compatibility with Symfony 7.4
        if (Kernel::VERSION_ID < 80000) {
            yield 'Doctrine style' => [new UserTotpCode(['message' => 'myMessage', 'translationDomain' => 'myDomain'])];
        }

        yield 'named arguments' => [new UserTotpCode(message: 'myMessage', translationDomain: 'myDomain')];
    }

    #[Test]
    #[DataProvider('emptyTotpCodeData')]
    public function validate_emptyCode_raisedViolation(string|null $code): void
    {
        $constraint = new UserTotpCode(message: 'myMessage');

        $this->validator->validate($code, $constraint);

        $this->buildViolation('myMessage')
            ->setCode(UserTotpCode::INVALID_TOTP_CODE_ERROR)
            ->setTranslationDomain('myDomain')
            ->assertRaised();
    }

    /**
     * @return list<array{string|null}>
     */
    public static function emptyTotpCodeData(): array
    {
        return [
            [null],
            [''],
        ];
    }

    #[Test]
    public function validate_invalidUser_throwsException(): void
    {
        // Create a user that doesn't implement TwoFactorInterface
        $user = $this->createMock(UserInterface::class);

        $this->tokenStorage = $this->createTokenStorage($user);
        $this->validator = $this->createValidator();
        $this->validator->initialize($this->context);

        $this->expectException(ConstraintDefinitionException::class);

        $this->validator->validate('secret', new UserTotpCode());
    }

    private function createTotpConfiguration(): TotpConfigurationInterface
    {
        return $this->createMock(TotpConfigurationInterface::class);
    }

    private function createUser(): UserWithTwoFactorInterface
    {
        $configuration = $this->createTotpConfiguration();

        $mock = $this->createMock(UserWithTwoFactorInterface::class);
        $mock
            ->expects($this->any())
            ->method('getTotpAuthenticationConfiguration')
            ->willReturn($configuration);

        return $mock;
    }

    private function createTotpAuthenticator(): TotpAuthenticatorInterface&MockObject
    {
        return $this->createMock(TotpAuthenticatorInterface::class);
    }

    private function createTokenStorage(UserInterface|null $user = null): TokenStorageInterface
    {
        $token = $this->createAuthenticationToken($user);

        $mock = $this->createMock(TokenStorageInterface::class);
        $mock
            ->expects($this->any())
            ->method('getToken')
            ->willReturn($token);

        return $mock;
    }

    private function createAuthenticationToken(UserInterface|null $user = null): TokenInterface
    {
        $mock = $this->createMock(TokenInterface::class);
        $mock
            ->expects($this->any())
            ->method('getUser')
            ->willReturn($user);

        return $mock;
    }
}
