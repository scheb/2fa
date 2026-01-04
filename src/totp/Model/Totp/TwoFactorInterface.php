<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Model\Totp;

interface TwoFactorInterface
{
    /**
     * Return true if the user should do TOTP authentication.
     */
    public function isTotpAuthenticationEnabled(): bool;

    /**
     * Return the user name. This is used in QR code generation to display the username in the TOTP app. Return an
     * empty string, if you don't want it to show up in the app.
     */
    public function getTotpAuthenticationUsername(): string|null;

    /**
     * Return the configuration for TOTP authentication.
     */
    public function getTotpAuthenticationConfiguration(): TotpConfigurationInterface|null;
}
