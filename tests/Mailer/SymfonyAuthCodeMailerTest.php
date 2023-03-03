<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Mailer;

use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Mailer\SymfonyAuthCodeMailer;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use function current;

class SymfonyAuthCodeMailerTest extends TestCase
{
    private MockObject|MailerInterface $symfonyMailer;
    private SymfonyAuthCodeMailer $mailer;

    protected function setUp(): void
    {
        $this->symfonyMailer = $this->createMock(MailerInterface::class);
        $this->mailer = new SymfonyAuthCodeMailer($this->symfonyMailer, 'sender@example.com', 'Sender Name', 'Authentication Code');
    }

    /**
     * @test
     */
    public function sendAuthCode_hasAuthCode_sendEmail(): void
    {
        // Stub the user object
        $user = $this->createMock(TwoFactorInterface::class);
        $user
            ->expects($this->any())
            ->method('getEmailAuthRecipient')
            ->willReturn('recipient@example.com');
        $user
            ->expects($this->any())
            ->method('getEmailAuthCode')
            ->willReturn('1234');

        $messageValidator = function ($mail) {
            /** @var Email $mail */
            $this->assertInstanceOf(Email::class, $mail);
            $this->assertEquals('recipient@example.com', current($mail->getTo())->getAddress());
            $this->assertEquals('sender@example.com', current($mail->getFrom())->getAddress());
            $this->assertEquals('Authentication Code', $mail->getSubject());
            $this->assertEquals('1234', $mail->getBody()->bodyToString());

            return true;
        };

        // Expect mail to be sent
        $this->symfonyMailer
            ->expects($this->once())
            ->method('send')
            ->with($this->callback($messageValidator));

        $this->mailer->sendAuthCode($user);
    }

    /**
     * @test
     */
    public function sendAuthCode_hasAuthCode_sendEmailWithoutSender(): void
    {
        $mailer = new SymfonyAuthCodeMailer($this->symfonyMailer, null, null, null);

        // Stub the user object
        $user = $this->createMock(TwoFactorInterface::class);
        $user
            ->expects($this->any())
            ->method('getEmailAuthRecipient')
            ->willReturn('recipient@example.com');
        $user
            ->expects($this->any())
            ->method('getEmailAuthCode')
            ->willReturn('1234');

        $messageValidator = function (Email $mail) {
            // normally set by the mailer in a listener https://github.com/symfony/mailer/blob/8fa150355115ea09238858ae3cfaf249fd1fd5ed/EventListener/EnvelopeListener.php#L49
            // can be removed when min version is symfony 6.1 where sender is to check the message
            $mail->sender('no-reply@example.com');

            $this->assertEquals('recipient@example.com', current($mail->getTo())->getAddress());
            $this->assertFalse(current($mail->getFrom()));
            $this->assertEquals('Authentication Code', $mail->getSubject());
            $this->assertEquals('1234', $mail->getBody()->bodyToString());

            return true;
        };

        // Expect mail to be sent
        $this->symfonyMailer
            ->expects($this->once())
            ->method('send')
            ->with($this->callback($messageValidator));

        $mailer->sendAuthCode($user);
    }

    /**
     * @test
     */
    public function sendAuthCode_nullAuthCode_sendEmail(): void
    {
        // Stub the user object
        $user = $this->createMock(TwoFactorInterface::class);
        $user
            ->expects($this->any())
            ->method('getEmailAuthRecipient')
            ->willReturn('recipient@example.com');
        $user
            ->expects($this->any())
            ->method('getEmailAuthCode')
            ->willReturn(null);

        // Expect no mail sent
        $this->symfonyMailer
            ->expects($this->never())
            ->method('send');

        $this->mailer->sendAuthCode($user);
    }
}
