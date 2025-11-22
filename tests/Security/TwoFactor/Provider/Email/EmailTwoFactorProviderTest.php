<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Provider\Email;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\EmailCodeEvents;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorCodeEvent;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Email\EmailTwoFactorProvider;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Email\Generator\CodeGeneratorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorFormRendererInterface;
use Scheb\TwoFactorBundle\Tests\EventDispatcherTestHelper;
use Scheb\TwoFactorBundle\Tests\TestCase;
use stdClass;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class EmailTwoFactorProviderTest extends TestCase
{
    use EventDispatcherTestHelper;

    private const string VALID_AUTH_CODE = 'validCode';
    private const string INVALID_AUTH_CODE = 'invalidCode';
    private const string VALID_AUTH_CODE_WITH_SPACES = ' valid Code ';

    private MockObject&CodeGeneratorInterface $generator;
    private EmailTwoFactorProvider $provider;

    protected function setUp(): void
    {
        $this->generator = $this->createMock(CodeGeneratorInterface::class);
        $formRenderer = $this->createMock(TwoFactorFormRendererInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->provider = new EmailTwoFactorProvider($this->generator, $formRenderer, $this->eventDispatcher);
    }

    private function createUser(bool $emailAuthEnabled = true): MockObject&UserWithTwoFactorInterface
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

    private function createAuthenticationContext(UserInterface|null $user = null): MockObject&AuthenticationContextInterface
    {
        $authContext = $this->createMock(AuthenticationContextInterface::class);
        $authContext
            ->expects($this->any())
            ->method('getUser')
            ->willReturn($user ?: $this->createUser());

        return $authContext;
    }

    #[Test]
    public function beginAuthentication_twoFactorPossible_returnTrue(): void
    {
        $user = $this->createUser(true);
        $context = $this->createAuthenticationContext($user);

        $returnValue = $this->provider->beginAuthentication($context);
        $this->assertTrue($returnValue);
    }

    #[Test]
    public function beginAuthentication_twoFactorDisabled_returnFalse(): void
    {
        $user = $this->createUser(false);
        $context = $this->createAuthenticationContext($user);

        $returnValue = $this->provider->beginAuthentication($context);
        $this->assertFalse($returnValue);
    }

    #[Test]
    public function beginAuthentication_interfaceNotImplemented_returnFalse(): void
    {
        $user = $this->createMock(UserInterface::class);
        $context = $this->createAuthenticationContext($user);

        $returnValue = $this->provider->beginAuthentication($context);
        $this->assertFalse($returnValue);
    }

    #[Test]
    public function prepareAuthentication_interfaceNotImplemented_doNothing(): void
    {
        $user = new stdClass();

        // Mock the CodeGenerator
        $this->generator
            ->expects($this->never())
            ->method('generateAndSend');

        $this->provider->prepareAuthentication($user);
    }

    #[Test]
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

    #[Test]
    public function prepareAuthentication_codeGenerated_dispatchSentEvent(): void
    {
        $user = $this->createUser(true);
        $user
            ->expects($this->any())
            ->method('getEmailAuthCode')
            ->willReturn(self::VALID_AUTH_CODE);

        $event = new TwoFactorCodeEvent($user, self::VALID_AUTH_CODE);
        $this->expectDispatchOneEvent($event, EmailCodeEvents::SENT);

        $this->provider->prepareAuthentication($user);
    }

    #[Test]
    public function validateAuthenticationCode_noTwoFactorUser_returnFalse(): void
    {
        $user = new stdClass();
        $returnValue = $this->provider->validateAuthenticationCode($user, 'code');
        $this->assertFalse($returnValue);
    }

    #[Test]
    public function validateAuthenticationCode_validCodeGiven_returnTrue(): void
    {
        $user = $this->createUser();
        $returnValue = $this->provider->validateAuthenticationCode($user, self::VALID_AUTH_CODE);
        $this->assertTrue($returnValue);
    }

    #[Test]
    public function validateAuthenticationCode_validCodeWithSpaces_returnTrue(): void
    {
        $user = $this->createUser();
        $returnValue = $this->provider->validateAuthenticationCode($user, self::VALID_AUTH_CODE_WITH_SPACES);
        $this->assertTrue($returnValue);
    }

    #[Test]
    public function validateAuthenticationCode_validCodeGiven_returnFalse(): void
    {
        $user = $this->createUser();
        $returnValue = $this->provider->validateAuthenticationCode($user, self::INVALID_AUTH_CODE);
        $this->assertFalse($returnValue);
    }

    #[Test]
    public function validateAuthenticationCode_validCode_dispatchCheckAndValidEvent(): void
    {
        $user = $this->createUser();

        $event = new TwoFactorCodeEvent($user, self::VALID_AUTH_CODE);
        $this->expectDispatchConsecutiveEvents([
            [$event, EmailCodeEvents::CHECK],
            [$event, EmailCodeEvents::VALID],
        ]);

        $this->provider->validateAuthenticationCode($user, self::VALID_AUTH_CODE);
    }

    #[Test]
    public function validateAuthenticationCode_invalidCode_dispatchCheckAndInvalidEvent(): void
    {
        $user = $this->createUser();

        $event = new TwoFactorCodeEvent($user, self::INVALID_AUTH_CODE);
        $this->expectDispatchConsecutiveEvents([
            [$event, EmailCodeEvents::CHECK],
            [$event, EmailCodeEvents::INVALID],
        ]);

        $this->provider->validateAuthenticationCode($user, self::INVALID_AUTH_CODE);
    }
}
