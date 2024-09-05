<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Event;

class EmailTwoFactorEvents
{
    public const EMAIL_CODE_VALIDATED = 'scheb_two_factor.email_code.validated';

    private function __construct()
    {
    }
}
