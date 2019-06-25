<?php

namespace Scheb\TwoFactorBundle\Model\Totp;

interface TwoFactorInterface
{
    /**
     * Return true if the user should do TOTP authentication.
     *
     * @return bool
     */
    public function isTotpAuthenticationEnabled(): bool;

    /**
     * Return the user name.
     *
     * @return string
     */
    public function getTotpAuthenticationUsername(): string;

    /**
     * Return the configuration for TOTP authentication.
     *
     * @return TotpConfigurationInterface|null
     */
    public function getTotpAuthenticationConfiguration(): ?TotpConfigurationInterface;
}
