<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Model\Totp;

interface TotpConfigurationInterface
{
    /**
     * @return string Base32 encoded secret key
     */
    public function getSecret(): string;

    /**
     * @return string Hashing algorithm to be used
     */
    public function getAlgorithm(): string;

    /**
     * @return int Period in seconds, when the one-time password changes
     */
    public function getPeriod(): int;

    /**
     * @return int Number of digits of the one-time password
     */
    public function getDigits(): int;
}
