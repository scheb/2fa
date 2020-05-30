<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Http\Authenticator\Passport\Badge;

use Symfony\Component\Security\Http\Authenticator\Passport\Badge\BadgeInterface;

class TrustedDeviceBadge implements BadgeInterface
{
    public function isResolved(): bool
    {
        return true;
    }
}
