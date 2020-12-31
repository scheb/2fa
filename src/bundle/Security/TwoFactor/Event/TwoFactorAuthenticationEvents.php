<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Event;

/**
 * @final
 */
class TwoFactorAuthenticationEvents
{
    /**
     * When two-factor authentication is required from the user. This normally results in a redirect to the two-factor
     * authentication form.
     */
    public const REQUIRE = 'scheb_two_factor.authentication.require';

    /**
     * When the two-factor authentication form is shown.
     */
    public const FORM = 'scheb_two_factor.authentication.form';

    /**
     * When two-factor authentication is attempted, dispatched before the code is checked.
     */
    public const ATTEMPT = 'scheb_two_factor.authentication.attempt';

    /**
     * When two-factor authentication was successful (code was valid) for a single provider.
     */
    public const SUCCESS = 'scheb_two_factor.authentication.success';

    /**
     * When two-factor authentication failed (code was invalid) for a single provider.
     */
    public const FAILURE = 'scheb_two_factor.authentication.failure';

    /**
     * When the entire two-factor authentication process was completed successfully, that means two-factor authentication
     * was successful for all providers and the user is now fully authenticated.
     */
    public const COMPLETE = 'scheb_two_factor.authentication.complete';
}
