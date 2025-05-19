<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * @final
 */
class TwoFactorCodeReusedEvent extends Event
{
    public function __construct(
        private readonly object $user,
        private readonly string $code,
    ) {
    }

    public function getUser(): object
    {
        return $this->user;
    }

    public function getCode(): string
    {
        return $this->code;
    }
}
