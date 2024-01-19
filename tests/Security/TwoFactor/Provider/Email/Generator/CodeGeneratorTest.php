<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Provider\Email\Generator;

use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Mailer\AuthCodeMailerInterface;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;
use Scheb\TwoFactorBundle\Model\PersisterInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Email\Generator\CodeGenerator;
use Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Provider\Email\TestableTwoFactorInterface;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\Clock\MockClock;

class CodeGeneratorTest extends TestCase
{
    private MockObject|PersisterInterface $persister;
    private MockObject|AuthCodeMailerInterface $mailer;
    private TestableCodeGenerator $authCodeManager;
    private MockClock $clock;

    protected function setUp(): void
    {
        $this->persister = $this->createMock(PersisterInterface::class);

        $this->mailer = $this->createMock(AuthCodeMailerInterface::class);

        $this->clock = new MockClock();

        $this->authCodeManager = new TestableCodeGenerator($this->persister, $this->mailer, 5, 'PT5M', $this->clock);
        $this->authCodeManager->testCode = 12345;
    }

    /**
     * @test
     */
    public function generateAndSend_useOriginalCodeGenerator_codeBetweenRange(): void
    {
        // Mock the user object
        $user = $this->createMock(TwoFactorInterface::class);
        $user
            ->expects($this->once())
            ->method('setEmailAuthCode')
            ->with($this->logicalAnd(
                $this->greaterThanOrEqual(10000),
                $this->lessThanOrEqual(99999),
            ));

        // Construct test subject with original class
        $authCodeManager = new CodeGenerator($this->persister, $this->mailer, 5);
        $authCodeManager->generateAndSend($user);
    }

    /**
     * @test
     */
    public function generateAndSend_checkCodeRange_validMinAndMax(): void
    {
        // Stub the user object
        $user = $this->createMock(TwoFactorInterface::class);

        $this->authCodeManager->generateAndSend($user);

        // Validate min and max value
        $this->assertEquals(10000, $this->authCodeManager->lastMin);
        $this->assertEquals(99999, $this->authCodeManager->lastMax);
    }

    /**
     * @test
     */
    public function generateAndSend_generateNewCode_persistsCode(): void
    {
        // Mock the user object
        $user = $this->createMock(TwoFactorInterface::class);
        $user
            ->expects($this->once())
            ->method('setEmailAuthCode')
            ->with(12345);

        // Mock the persister
        $this->persister
            ->expects($this->once())
            ->method('persist')
            ->with($user);

        $this->authCodeManager->generateAndSend($user);
    }

    /**
     * @test
     */
    public function generateAndSend_generateNewCode_persistsCreatedAt(): void
    {
        // Mock the user object
        $user = $this->createMock(TestableTwoFactorInterface::class);
        $user
            ->expects($this->once())
            ->method('setEmailAuthCodeCreatedAt')
            ->with($this->clock->now());

        // Mock the persister
        $this->persister
            ->expects($this->once())
            ->method('persist')
            ->with($user);

        $this->authCodeManager->generateAndSend($user);
    }

    /**
     * @test
     */
    public function generateAndSend_generateNewCode_sendMail(): void
    {
        // Stub the user object
        $user = $this->createMock(TwoFactorInterface::class);

        // Mock the mailer
        $this->mailer
            ->expects($this->once())
            ->method('sendAuthCode')
            ->with($user);

        $this->authCodeManager->generateAndSend($user);
    }

    /**
     * @test
     */
    public function reSend_whenCalled_sendMail(): void
    {
        // Stub the user object
        $user = $this->createMock(TwoFactorInterface::class);

        // Mock the mailer
        $this->mailer
            ->expects($this->once())
            ->method('sendAuthCode')
            ->with($user);

        $this->authCodeManager->reSend($user);
    }

    /**
     * @test
     */
    public function isExpired_withoutExpiration_returnsFalse(): void
    {
        $user = $this->createMock(TestableTwoFactorInterface::class);
        $user
            ->expects($this->never())
            ->method('getEmailAuthCodeCreatedAt');

        $codeGenerator = new TestableCodeGenerator($this->persister, $this->mailer, 5, null, $this->clock);

        $this->assertFalse($codeGenerator->isCodeExpired($user));
    }

    /**
     * @test
     */
    public function isExpired_withNotExpiringCode_returnsFalse(): void
    {
        $user = $this->createMock(TestableTwoFactorInterface::class);
        $user
            ->expects($this->once())
            ->method('getEmailAuthCodeCreatedAt')
            ->willReturn(null);

        $this->assertFalse($this->authCodeManager->isCodeExpired($user));
    }

    /**
     * @test
     */
    public function isExpired_withNotExpiredCode_returnsFalse(): void
    {
        $user = $this->createMock(TestableTwoFactorInterface::class);
        $user
            ->expects($this->once())
            ->method('getEmailAuthCodeCreatedAt')
            ->willReturn($this->clock->now());

        $this->assertFalse($this->authCodeManager->isCodeExpired($user));
    }

    /**
     * @test
     */
    public function isExpired_withExpiredCode_returnsTrue(): void
    {
        $user = $this->createMock(TestableTwoFactorInterface::class);
        $user
            ->expects($this->once())
            ->method('getEmailAuthCodeCreatedAt')
            ->willReturn(new DateTimeImmutable('-6 minutes'));

        $this->assertTrue($this->authCodeManager->isCodeExpired($user));
    }
}
