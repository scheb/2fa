<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Provider;

/**
 * Decrypts a TOTP secret that the application stores encrypted.
 */
interface SecretDecryptorInterface
{
    /**
     * Return the Base32 encoded secret from the encrypted value that is stored with the user.
     */
    public function decrypt(string $secret): string;
}
