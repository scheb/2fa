<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Event;

/**
 * @final
 */
class GoogleAuthenticatorCodeEvents
{
    /**
     * When a code is about to be checked by the Google Authenticator provider.
     */
    public const CHECK = 'scheb_two_factor.provider.google.check';

    /**
     * When the code was deemed to be valid by the Google Authenticator provider.
     */
    public const VALID = 'scheb_two_factor.provider.google.valid';

    /**
     * When the code was deemed to be invalid by the Google Authenticator provider.
     */
    public const INVALID = 'scheb_two_factor.provider.google.invalid';
}
