<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Mailer;

use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * @final
 */
class SymfonyAuthCodeMailer implements AuthCodeMailerInterface
{
    private Address|string|null $senderAddress = null;

    public function __construct(
        private MailerInterface $mailer,
        ?string $senderEmail,
        ?string $senderName,
    ) {
        if (null !== $senderEmail && null !== $senderName) {
            $this->senderAddress = new Address($senderEmail, $senderName);
        } elseif ($senderEmail) {
            $this->senderAddress = $senderEmail;
        }
    }

    public function sendAuthCode(TwoFactorInterface $user): void
    {
        $authCode = $user->getEmailAuthCode();
        if (null === $authCode) {
            return;
        }

        $message = new Email();
        $message
            ->to($user->getEmailAuthRecipient())
            ->subject('Authentication Code')
            ->text($authCode);

        if (null !== $this->senderAddress) {
            $message->from($this->senderAddress);
        }

        $this->mailer->send($message);
    }
}
