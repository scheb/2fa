<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Trusted;

use DateTimeImmutable;
use Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\TrustedCookieResponseListener;

/**
 * Make the current DateTime testable.
 */
class TestableTrustedCookieResponseListener extends TrustedCookieResponseListener
{
    public DateTimeImmutable $now;

    protected function getDateTimeNow(): DateTimeImmutable
    {
        return $this->now;
    }
}
