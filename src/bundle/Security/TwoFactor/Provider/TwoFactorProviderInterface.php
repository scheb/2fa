<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Provider;

use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;

interface TwoFactorProviderInterface
{
    /**
     * Return true when two-factor authentication process should be started.
     */
    public function beginAuthentication(AuthenticationContextInterface $context): bool;

    /**
     * Do all steps necessary to prepare authentication, e.g. generate & send a code.
     *
     * @param mixed $user
     */
    public function prepareAuthentication($user): void;

    /**
     * Validate the two-factor authentication code.
     *
     * @param mixed $user
     */
    public function validateAuthenticationCode($user, string $authenticationCode): bool;

    /**
     * Return the form renderer for two-factor authentication.
     */
    public function getFormRenderer(): TwoFactorFormRendererInterface;
}
