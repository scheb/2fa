<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp;

use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface;

interface TotpAuthenticatorInterface
{
    /**
     * Validates the code, which was entered by the user.
     *
     * @param non-empty-string $code
     */
    public function checkCode(TwoFactorInterface $user, string $code): bool;

    /**
     * Generate the content for a QR-Code to be scanned by the authenticator app.
     */
    public function getQRContent(TwoFactorInterface $user): string;

    /**
     * Generate a new secret for TOTP authentication.
     *
     * @return non-empty-string
     */
    public function generateSecret(): string;
}
