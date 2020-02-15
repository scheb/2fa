<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Model;

interface TrustedDeviceInterface
{
    /**
     * Return version for the trusted token. Increase version to invalidate all trusted token of the user.
     */
    public function getTrustedTokenVersion(): int;
}
