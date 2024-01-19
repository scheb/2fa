<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Provider\Email;

use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Email\EmailTwoFactorProvider;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Email\Generator\CodeGeneratorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorFormRendererInterface;
use Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Provider\Email\Generator\TestableCodeGeneratorInterface;
use Scheb\TwoFactorBundle\Tests\TestCase;
use stdClass;
use Symfony\Component\Security\Core\User\UserInterface;

class EmailTwoFactorProviderTest extends TestCase
{
    private const VALID_AUTH_CODE = 'validCode';
    private const INVALID_AUTH_CODE = 'invalidCode';
    private const VALID_AUTH_CODE_WITH_SPACES = ' valid Code ';

    private MockObject|CodeGeneratorInterface $generator;
    private MockObject|TwoFactorFormRendererInterface $formRenderer;
    private EmailTwoFactorProvider $provider;

    protected function setUp(): void
    {
        $this->generator = $this->createMock(TestableCodeGeneratorInterface::class);
        $this->formRenderer = $this->createMock(TwoFactorFormRendererInterface::class);
        $this->provider = new EmailTwoFactorProvider($this->generator, $this->formRenderer, true);
    }

    private function createUser(bool $emailAuthEnabled = true): MockObject|UserWithTwoFactorInterface
    {
        $user = $this->createMock(UserWithTwoFactorInterface::class);
        $user
            ->expects($this->any())
            ->method('isEmailAuthEnabled')
            ->willReturn($emailAuthEnabled);
        $user
            ->expects($this->any())
            ->method('getEmailAuthCode')
            ->willReturn(self::VALID_AUTH_CODE);

        return $user;
    }

    private function createAuthenticationContext(UserInterface|null $user = null): MockObject|AuthenticationContextInterface
    {
        $authContext = $this->createMock(AuthenticationContextInterface::class);
        $authContext
            ->expects($this->any())
            ->method('getUser')
            ->willReturn($user ?: $this->createUser());

        return $authContext;
    }

    /**
     * @test
     */
    public function beginAuthentication_twoFactorPossible_returnTrue(): void
    {
        $user = $this->createUser(true);
        $context = $this->createAuthenticationContext($user);

        $returnValue = $this->provider->beginAuthentication($context);
        $this->assertTrue($returnValue);
    }

    /**
     * @test
     */
    public function beginAuthentication_twoFactorDisabled_returnFalse(): void
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
    public function prepareAuthentication_interfaceNotImplemented_doNothing(): void
    {
        $user = new stdClass();

        // Mock the CodeGenerator
        $this->generator
            ->expects($this->never())
            ->method('generateAndSend');

        $this->provider->prepareAuthentication($user);
    }

    /**
     * @test
     */
    public function prepareAuthentication_interfaceImplemented_codeGenerated(): void
    {
        $user = $this->createUser(true);

        // Mock the CodeGenerator
        $this->generator
            ->expects($this->once())
            ->method('generateAndSend')
            ->with($user);

        $this->provider->prepareAuthentication($user);
    }

    /**
     * @test
     */
    public function validateAuthenticationCode_noTwoFactorUser_returnFalse(): void
    {
        $user = new stdClass();
        $returnValue = $this->provider->validateAuthenticationCode($user, 'code');
        $this->assertFalse($returnValue);
    }

    /**
     * @test
     */
    public function validateAuthenticationCode_validCodeGiven_returnTrue(): void
    {
        $user = $this->createUser();
        $returnValue = $this->provider->validateAuthenticationCode($user, self::VALID_AUTH_CODE);
        $this->assertTrue($returnValue);
    }

    /**
     * @test
     */
    public function validateAuthenticationCode_validCodeWithSpaces_returnTrue(): void
    {
        $user = $this->createUser();
        $returnValue = $this->provider->validateAuthenticationCode($user, self::VALID_AUTH_CODE_WITH_SPACES);
        $this->assertTrue($returnValue);
    }

    /**
     * @test
     */
    public function validateAuthenticationCode_validCodeGiven_returnFalse(): void
    {
        $user = $this->createUser();
        $returnValue = $this->provider->validateAuthenticationCode($user, self::INVALID_AUTH_CODE);
        $this->assertFalse($returnValue);
    }

    /**
     * @test
     */
    public function validateAuthenticationCode_withNotExpiredCode_returnsTrue(): void
    {
        $user = $this->createUser();

        $this->generator
            ->expects($this->once())
            ->method('isCodeExpired')
            ->with($user)
            ->willReturn(false);

        $this->assertTrue($this->provider->validateAuthenticationCode($user, self::VALID_AUTH_CODE));
    }

    /**
     * @test
     */
    public function validateAuthenticationCode_withExpiredCode_regeneratesCode(): void
    {
        $user = $this->createUser();

        $this->generator
            ->expects($this->once())
            ->method('isCodeExpired')
            ->with($user)
            ->willReturn(true);

        $this->generator
            ->expects($this->once())
            ->method('generateAndSend')
            ->with($user);

        $this->assertFalse($this->provider->validateAuthenticationCode($user, self::VALID_AUTH_CODE));
    }

    public function validateAuthenticationCode_withExpiredCode_doesNotRegenerateCode(): void
    {
        $provider = new EmailTwoFactorProvider($this->generator, $this->formRenderer, false);

        $user = $this->createUser();

        $this->generator
            ->expects($this->once())
            ->method('isCodeExpired')
            ->with($user)
            ->willReturn(true);

        $this->generator
            ->expects($this->never())
            ->method('generateAndSend');

        $this->assertFalse($provider->validateAuthenticationCode($user, self::VALID_AUTH_CODE));
    }
}
