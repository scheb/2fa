<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Provider;

use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;

/**
 * @method bool needsPreparation()
 */
interface TwoFactorProviderInterface
{
    /**
     * Return true when two-factor authentication process should be started.
     */
    public function beginAuthentication(AuthenticationContextInterface $context): bool;

    /**
     * Determine whether this Provider needs to be prepared (if the prepareAuthentication method needs to be called).
     *
     * In version 9, this method will be introduced, and all providers will need to implement it.
     * Currently, it will be called, but is not required.
     *
     * public function needsPreparation(): bool;
     */

    /**
     * Do all steps necessary to prepare authentication, e.g. generate & send a code.
     */
    public function prepareAuthentication(object $user): void;

    /**
     * Validate the two-factor authentication code.
     */
    public function validateAuthenticationCode(object $user, string $authenticationCode): bool;

    /**
     * Return the form renderer for two-factor authentication.
     */
    public function getFormRenderer(): TwoFactorFormRendererInterface;
}
