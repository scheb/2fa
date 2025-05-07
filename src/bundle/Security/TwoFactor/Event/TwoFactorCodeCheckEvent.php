<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Event;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @final
 */
class TwoFactorCodeCheckEvent extends Event
{
    /**
     * @param UserInterface $user
     */
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
