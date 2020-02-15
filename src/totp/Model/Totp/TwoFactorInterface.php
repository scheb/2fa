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
     * Return the user name.
     */
    public function getTotpAuthenticationUsername(): string;

    /**
     * Return the configuration for TOTP authentication.
     */
    public function getTotpAuthenticationConfiguration(): ?TotpConfigurationInterface;
}
