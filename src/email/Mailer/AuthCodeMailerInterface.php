<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Mailer;

use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;

interface AuthCodeMailerInterface
{
    /**
     * Send the auth code to the user via email.
     */
    public function sendAuthCode(TwoFactorInterface $user): void;
}
