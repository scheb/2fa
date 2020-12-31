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
    /**
     * @var string|null
     */
    private $server;

    /**
     * @var string|null
     */
    private $issuer;

    /**
     * @var int
     */
    private $digits;

    public function __construct(?string $server, ?string $issuer, int $digits)
    {
        $this->server = $server;
        $this->issuer = $issuer;
        $this->digits = $digits;
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
