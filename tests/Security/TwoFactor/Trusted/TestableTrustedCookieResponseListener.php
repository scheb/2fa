<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Trusted;

use DateTime;
use Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\TrustedCookieResponseListener;

/**
 * Make the current DateTime testable.
 */
class TestableTrustedCookieResponseListener extends TrustedCookieResponseListener
{
    public DateTime $now;

    protected function getDateTimeNow(): DateTime
    {
        return $this->now;
    }
}
