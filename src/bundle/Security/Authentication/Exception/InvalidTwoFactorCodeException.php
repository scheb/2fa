<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Authentication\Exception;

use Symfony\Component\Security\Core\Exception\BadCredentialsException;

/**
 * @final
 */
class InvalidTwoFactorCodeException extends BadCredentialsException
{
    public const MESSAGE = 'Invalid two-factor authentication code.';
    private const MESSAGE_KEY = 'code_invalid';

    public function getMessageKey(): string
    {
        return self::MESSAGE_KEY;
    }
}
