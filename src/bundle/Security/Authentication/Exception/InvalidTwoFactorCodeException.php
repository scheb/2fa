<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Authentication\Exception;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

class InvalidTwoFactorCodeException extends AuthenticationException
{
    /**
     * @var string
     */
    private $messageKey = 'Invalid two-factor authentication code.';

    public function getMessageKey()
    {
        return $this->messageKey;
    }

    public function setMessageKey(string $messageKey): void
    {
        $this->messageKey = $messageKey;
    }

    public function serialize()
    {
        return serialize($this->__serialize());
    }

    // Symfony 4.3 / PHP 7.4
    public function __serialize(): array
    {
        $parentHasNewInterface = method_exists(get_parent_class($this), '__serialize');
        $parentData = $parentHasNewInterface ? parent::__serialize() : parent::serialize();

        return [$this->messageKey, $parentData];
    }

    public function unserialize($serialized)
    {
        $this->__unserialize(\is_array($serialized) ? $serialized : unserialize($serialized));
    }

    // Symfony 4.3 / PHP 7.4
    public function __unserialize(array $data): void
    {
        [$this->messageKey, $parentData] = $data;
        if (\is_array($parentData)) {
            parent::__unserialize($parentData);
        } else {
            parent::unserialize($parentData);
        }
    }
}
