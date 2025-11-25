<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Authentication\Exception;

use Override;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

/**
 * @final
 */
class ReusedTwoFactorCodeException extends BadCredentialsException
{
    public const string MESSAGE = 'Reused two-factor authentication code.';
    private const string MESSAGE_KEY = 'code_reused';

    #[Override]
    public function getMessageKey(): string
    {
        return self::MESSAGE_KEY;
    }
}
