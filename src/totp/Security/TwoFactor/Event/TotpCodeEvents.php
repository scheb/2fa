<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Event;

/**
 * @final
 */
class TotpCodeEvents
{
    /**
     * When a code is about to be checked by the TOTP provider.
     */
    public const string CHECK = 'scheb_two_factor.provider.totp.check';

    /**
     * When the code was deemed to be valid by the TOTP provider.
     */
    public const string VALID = 'scheb_two_factor.provider.totp.valid';

    /**
     * When the code was deemed to be invalid by the TOTP provider.
     */
    public const string INVALID = 'scheb_two_factor.provider.totp.invalid';
}
