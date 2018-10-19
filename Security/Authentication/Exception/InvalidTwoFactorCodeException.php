<?php

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
        return serialize(array(
            $this->messageKey,
            parent::serialize(),
        ));
    }

    public function unserialize($str)
    {
        list($this->messageKey, $parentData) = unserialize($str);
        parent::unserialize($parentData);
    }
}
