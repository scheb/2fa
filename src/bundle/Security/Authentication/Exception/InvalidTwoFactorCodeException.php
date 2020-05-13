<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Authentication\Exception;

use Symfony\Component\Security\Core\Exception\BadCredentialsException;

class InvalidTwoFactorCodeException extends BadCredentialsException
{
    /**
     * @var string
     */
    private $messageKey = 'Invalid two-factor authentication code.';

    public function getMessageKey(): string
    {
        return $this->messageKey;
    }

    public function setMessageKey(string $messageKey): void
    {
        $this->messageKey = $messageKey;
    }

    public function __serialize(): array
    {
        return [$this->messageKey, parent::__serialize()];
    }

    public function __unserialize(array $data): void
    {
        [$this->messageKey, $parentData] = $data;
        parent::__unserialize($parentData);
    }
}
