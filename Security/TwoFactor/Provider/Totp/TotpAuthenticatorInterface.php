<?php

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp;

use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface;

interface TotpAuthenticatorInterface
{
    /**
     * Validates the code, which was entered by the user.
     *
     * @param TwoFactorInterface $user
     * @param string             $code
     *
     * @return bool
     */
    public function checkCode(TwoFactorInterface $user, string $code): bool;

    /**
     * Generate the content for a QR-Code to be scanned by the authenticator app.
     *
     * @param TwoFactorInterface $user
     *
     * @return string
     */
    public function getQRContent(TwoFactorInterface $user): string;

    /**
     * Generate a new secret for TOTP authentication.
     *
     * @return string
     */
    public function generateSecret(): string;
}
