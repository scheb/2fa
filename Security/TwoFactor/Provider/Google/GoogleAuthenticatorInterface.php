<?php

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google;

use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface;

interface GoogleAuthenticatorInterface
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
     * Generate the content for a QR-Code to be scanned by Google Authenticator.
     *
     * @param TwoFactorInterface $user
     *
     * @return string
     */
    public function getQRContent(TwoFactorInterface $user): string;

    /**
     * Generate a new secret for Google Authenticator.
     *
     * @return string
     */
    public function generateSecret(): string;
}
