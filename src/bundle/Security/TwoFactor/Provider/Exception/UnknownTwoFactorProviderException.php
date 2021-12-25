<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Exception;

use InvalidArgumentException;

/**
 * @final
 */
class UnknownTwoFactorProviderException extends InvalidArgumentException
{
}
