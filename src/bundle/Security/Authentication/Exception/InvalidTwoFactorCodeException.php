<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Authentication\Exception;

use Override;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

/**
 * @final
 */
class InvalidTwoFactorCodeException extends BadCredentialsException
{
    public const string MESSAGE = 'Invalid two-factor authentication code.';
    private const string MESSAGE_KEY = 'code_invalid';

    #[Override]
    public function getMessageKey(): string
    {
        return self::MESSAGE_KEY;
    }
}
