<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Event;

readonly class EmailCodeValidated
{
    public function __construct(
        public string $email,
    ) {
    }
}
