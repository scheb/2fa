<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Provider\Email;

use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Email\EmailTwoFactorProvider;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Email\Generator\CodeGeneratorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorFormRendererInterface;
use Scheb\TwoFactorBundle\Tests\TestCase;
use stdClass;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class EmailTwoFactorProviderTest extends TestCase
{
    private const VALID_AUTH_CODE = 'validCode';
    private const INVALID_AUTH_CODE = 'invalidCode';
    private const VALID_AUTH_CODE_WITH_SPACES = ' valid Code ';

    private MockObject|CodeGeneratorInterface $generator;
    private EmailTwoFactorProvider $provider;

    private MockObject|EventDispatcherInterface $eventDispatcher;

    protected function setUp(): void
    {
        $this->generator = $this->createMock(CodeGeneratorInterface::class);
        $formRenderer = $this->createMock(TwoFactorFormRendererInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->provider = new EmailTwoFactorProvider($this->generator, $formRenderer, $this->eventDispatcher);
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

    private function expectEmailValidatedEventDispatched(): void
    {
        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch');
    }

    private function expectEmailValidatedEventNotDispatched(): void
    {
        $this->eventDispatcher
            ->expects($this->never())
            ->method('dispatch');
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

        $this->expectEmailValidatedEventNotDispatched();
        $returnValue = $this->provider->validateAuthenticationCode($user, 'code');
        $this->assertFalse($returnValue);
    }

    /**
     * @test
     */
    public function validateAuthenticationCode_validCodeGiven_returnTrue(): void
    {
        $user = $this->createUser();

        $this->expectEmailValidatedEventDispatched();
        $returnValue = $this->provider->validateAuthenticationCode($user, self::VALID_AUTH_CODE);
        $this->assertTrue($returnValue);
    }

    /**
     * @test
     */
    public function validateAuthenticationCode_validCodeWithSpaces_returnTrue(): void
    {
        $user = $this->createUser();

        $this->expectEmailValidatedEventDispatched();
        $returnValue = $this->provider->validateAuthenticationCode($user, self::VALID_AUTH_CODE_WITH_SPACES);
        $this->assertTrue($returnValue);
    }

    /**
     * @test
     */
    public function validateAuthenticationCode_validCodeGiven_returnFalse(): void
    {
        $user = $this->createUser();

        $this->expectEmailValidatedEventNotDispatched();
        $returnValue = $this->provider->validateAuthenticationCode($user, self::INVALID_AUTH_CODE);
        $this->assertFalse($returnValue);
    }
}
