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
    public const string REQUIRE = 'scheb_two_factor.authentication.require';

    /**
     * When the two-factor authentication form is shown.
     */
    public const string FORM = 'scheb_two_factor.authentication.form';

    /**
     * When two-factor authentication is attempted, dispatched before the code is checked.
     */
    public const string ATTEMPT = 'scheb_two_factor.authentication.attempt';

    /**
     * When two-factor authentication was successful (code was valid) for a single provider.
     */
    public const string SUCCESS = 'scheb_two_factor.authentication.success';

    /**
     * When two-factor authentication failed (code was invalid) for a single provider.
     */
    public const string FAILURE = 'scheb_two_factor.authentication.failure';

    /**
     * When the entire two-factor authentication process was completed successfully, that means two-factor authentication
     * was successful for all providers and the user is now fully authenticated.
     */
    public const string COMPLETE = 'scheb_two_factor.authentication.complete';

    /**
     * When the two-factor authentication code is checked, right before the two-factor code is handed to the
     * actual providers.
     */
    public const string CHECK = 'scheb_two_factor.authentication.check';

    /**
     * When the two-factor code has been used already.
     */
    public const string CODE_REUSED = 'scheb_two_factor.authentication.code_reused';

    /**
     * When the two-factor process will not start because at least one 2fa condition is not met.
     */
    public const string SKIPPED = 'scheb_two_factor.authentication.skipped';
}
