<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Event;

/**
 * @final
 */
class EmailCodeEvents
{
    /**
     * When a code was sent by the email provider.
     */
    public const string SENT = 'scheb_two_factor.provider.email.sent';

    /**
     * When a code is about to be checked by the email provider.
     */
    public const string CHECK = 'scheb_two_factor.provider.email.check';

    /**
     * When the code was deemed to be valid by the email provider.
     */
    public const string VALID = 'scheb_two_factor.provider.email.valid';

    /**
     * When the code was deemed to be invalid by the email provider.
     */
    public const string INVALID = 'scheb_two_factor.provider.email.invalid';
}
