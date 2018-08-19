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
     * Return the Provisioning Uri
     * When an empty string or null is returned, the method is disabled.
     *
     * @return string
     */
    public function getTotpAuthenticationProvisioningUri(): ?string;
}
