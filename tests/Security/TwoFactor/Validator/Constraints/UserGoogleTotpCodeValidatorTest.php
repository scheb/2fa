<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Validator\Constraints;

use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Validator\Constraints\UserGoogleTotpCode;
use Scheb\TwoFactorBundle\Security\TwoFactor\Validator\Constraints\UserGoogleTotpCodeValidator;
use Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Provider\Google\UserWithTwoFactorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @implements ConstraintValidatorTestCase<UserGoogleTotpCodeValidator>
 */
class UserGoogleTotpCodeValidatorTest extends ConstraintValidatorTestCase
{
    protected TokenStorageInterface $tokenStorage;
    protected GoogleAuthenticatorInterface&MockObject $googleAuthenticator;

    protected function createValidator(): UserGoogleTotpCodeValidator
    {
        return new UserGoogleTotpCodeValidator($this->tokenStorage, $this->googleAuthenticator);
    }

    protected function setUp(): void
    {
        $user = $this->createUser();
        $this->tokenStorage = $this->createTokenStorage($user);
        $this->googleAuthenticator = $this->createGoogleAuthenticator();

        parent::setUp();
    }

    #[Test]
    #[DataProvider('provideConstraints')]
    public function validate_validCode_noViolation(UserGoogleTotpCode $constraint): void
    {
        $this->googleAuthenticator
            ->expects($this->any())
            ->method('checkCode')
            ->willReturn(true);

        $this->validator->validate('secret', $constraint);

        $this->assertNoViolation();
    }

    #[Test]
    #[DataProvider('provideConstraints')]
    public function validate_invalidCode_raisedViolation(UserGoogleTotpCode $constraint): void
    {
        $this->googleAuthenticator
            ->expects($this->any())
            ->method('checkCode')
            ->willReturn(false);

        $this->validator->validate('secret', $constraint);

        $this->buildViolation('myMessage')
            ->setCode(UserGoogleTotpCode::INVALID_TOTP_CODE_ERROR)
            ->setTranslationDomain('myDomain')
            ->assertRaised();
    }

    /**
     * @return Generator<string, list{UserGoogleTotpCode}>
     */
    public static function provideConstraints(): iterable
    {
        yield 'Doctrine style' => [new UserGoogleTotpCode(['message' => 'myMessage', 'translationDomain' => 'myDomain'])];

        yield 'named arguments' => [new UserGoogleTotpCode(message: 'myMessage', translationDomain: 'myDomain')];
    }

    #[Test]
    #[DataProvider('emptyTotpCodeData')]
    public function validate_emptyCode_raisedViolation(string|null $code): void
    {
        $constraint = new UserGoogleTotpCode(['message' => 'myMessage']);

        $this->validator->validate($code, $constraint);

        $this->buildViolation('myMessage')
            ->setCode(UserGoogleTotpCode::INVALID_TOTP_CODE_ERROR)
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

        $this->validator->validate('secret', new UserGoogleTotpCode());
    }

    protected function createUser(): UserWithTwoFactorInterface
    {
        return $this->createMock(UserWithTwoFactorInterface::class);
    }

    protected function createGoogleAuthenticator(): GoogleAuthenticatorInterface&MockObject
    {
        return $this->createMock(GoogleAuthenticatorInterface::class);
    }

    protected function createTokenStorage(UserInterface|null $user = null): TokenStorageInterface
    {
        $token = $this->createAuthenticationToken($user);

        $mock = $this->createMock(TokenStorageInterface::class);
        $mock
            ->expects($this->any())
            ->method('getToken')
            ->willReturn($token);

        return $mock;
    }

    protected function createAuthenticationToken(UserInterface|null $user = null): TokenInterface
    {
        $mock = $this->createMock(TokenInterface::class);
        $mock
            ->expects($this->any())
            ->method('getUser')
            ->willReturn($user);

        return $mock;
    }
}
