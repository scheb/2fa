<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google;

use OTPHP\TOTP;
use OTPHP\TOTPInterface;
use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface;

/**
 * @final
 */
class GoogleTotpFactory
{
    public function __construct(private ?string $server, private ?string $issuer, private int $digits)
    {
    }

    public function createTotpForUser(TwoFactorInterface $user): TOTPInterface
    {
        $totp = TOTP::create($user->getGoogleAuthenticatorSecret(), 30, 'sha1', $this->digits);

        $userAndHost = $user->getGoogleAuthenticatorUsername().($this->server ? '@'.$this->server : '');
        $totp->setLabel($userAndHost);

        if ($this->issuer) {
            $totp->setIssuer($this->issuer);
        }

        return $totp;
    }
}
