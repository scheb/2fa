<?php

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp;

use OTPHP\TOTP;
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
     * Generate the URL of a QR code, which can be scanned by Google Authenticator app.
     *
     * @param TOTP $totp
     *
     * @return string
     */
    public function getUrl(TOTP $totp): string;
}
