<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Event;

/**
 * @final
 */
class BackupCodeEvents
{
    /**
     * When a code is checked if it is a valid backup code.
     */
    public const string CHECK = 'scheb_two_factor.backup_code.check';

    /**
     * When the code was deemed to be a valid backup code.
     */
    public const string VALID = 'scheb_two_factor.backup_code.valid';

    /**
     * When the code was deemed to be an invalid backup code.
     */
    public const string INVALID = 'scheb_two_factor.backup_code.invalid';
}
