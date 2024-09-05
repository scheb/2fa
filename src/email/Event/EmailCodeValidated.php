<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Event;

class EmailCodeValidated
{
    /**
     * @var string
     */
    private $email;

    public function __construct(
        string $email
    ) {
        $this->email = $email;
    }

    public function getEmail(): string
    {
        return $this->email;
    }
}
