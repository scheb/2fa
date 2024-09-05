<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Event;

readonly class EmailCodeValidated
{
    public function __construct(
        public string $email,
    ) {
    }
}
