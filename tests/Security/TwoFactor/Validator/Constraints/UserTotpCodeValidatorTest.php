<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Validator\Constraints;

use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfigurationInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Validator\Constraints\UserTotpCode;
use Scheb\TwoFactorBundle\Security\TwoFactor\Validator\Constraints\UserTotpCodeValidator;
use Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Provider\Totp\UserWithTwoFactorInterface;
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
    protected TokenStorageInterface $tokenStorage;
    protected TotpAuthenticatorInterface&MockObject $totpAuthenticator;

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

    #[DataProvider('provideConstraints')]
    public function testTotpCodeIsValid(UserTotpCode $constraint): void
    {
        $this->totpAuthenticator
            ->expects(self::any())
            ->method('checkCode')
            ->willReturn(true);

        $this->validator->validate('secret', $constraint);

        $this->assertNoViolation();
    }

    #[DataProvider('provideConstraints')]
    public function testTotpCodeIsNotValid(UserTotpCode $constraint): void
    {
        $this->totpAuthenticator
            ->expects(self::any())
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
        yield 'Doctrine style' => [new UserTotpCode(['message' => 'myMessage', 'translationDomain' => 'myDomain'])];

        yield 'named arguments' => [new UserTotpCode(message: 'myMessage', translationDomain: 'myDomain')];
    }

    #[DataProvider('emptyTotpCodeData')]
    public function testEmptyTotpCodesAreNotValid(string|null $code): void
    {
        $constraint = new UserTotpCode(['message' => 'myMessage']);

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

    public function testUserIsNotValid(): void
    {
        $user = $this->createMock(UserInterface::class);

        $this->tokenStorage = $this->createTokenStorage($user);
        $this->validator = $this->createValidator();
        $this->validator->initialize($this->context);

        $this->expectException(ConstraintDefinitionException::class);

        $this->validator->validate('secret', new UserTotpCode());
    }

    protected function createTotpConfiguration(): TotpConfigurationInterface
    {
        return $this->createMock(TotpConfigurationInterface::class);
    }

    protected function createUser(): UserWithTwoFactorInterface
    {
        $configuration = $this->createTotpConfiguration();

        $mock = $this->createMock(UserWithTwoFactorInterface::class);
        $mock
            ->expects(self::any())
            ->method('getTotpAuthenticationConfiguration')
            ->willReturn($configuration);

        return $mock;
    }

    protected function createTotpAuthenticator(): TotpAuthenticatorInterface&MockObject
    {
        return $this->createMock(TotpAuthenticatorInterface::class);
    }

    protected function createTokenStorage(UserInterface|null $user = null): TokenStorageInterface
    {
        $token = $this->createAuthenticationToken($user);

        $mock = $this->createMock(TokenStorageInterface::class);
        $mock
            ->expects(self::any())
            ->method('getToken')
            ->willReturn($token);

        return $mock;
    }

    protected function createAuthenticationToken(UserInterface|null $user = null): TokenInterface
    {
        $mock = $this->createMock(TokenInterface::class);
        $mock
            ->expects(self::any())
            ->method('getUser')
            ->willReturn($user);

        return $mock;
    }
}
