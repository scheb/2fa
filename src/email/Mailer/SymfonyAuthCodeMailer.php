<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Mailer;

use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class SymfonyAuthCodeMailer implements AuthCodeMailerInterface
{
    /**
     * @var MailerInterface
     */
    private $mailer;

    /**
     * @var Address
     */
    private $senderAddress;

    public function __construct(MailerInterface $mailer, string $senderEmail, ?string $senderName)
    {
        $this->mailer = $mailer;
        $this->senderAddress = new Address($senderEmail, $senderName);
    }

    public function sendAuthCode(TwoFactorInterface $user): void
    {
        $message = new Email();
        $message
            ->to($user->getEmailAuthRecipient())
            ->from($this->senderAddress)
            ->subject('Authentication Code')
            ->text($user->getEmailAuthCode())
        ;
        $this->mailer->send($message);
    }
}
