<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Trusted;

use Lcobucci\JWT\Token\Plain;

class TrustedDeviceTokenEncoder
{
    /**
     * @var JwtTokenEncoder
     */
    private $jwtTokenEncoder;

    /**
     * @var int
     */
    private $trustedTokenLifetime;

    public function __construct(JwtTokenEncoder $jwtTokenEncoder, int $trustedTokenLifetime)
    {
        $this->jwtTokenEncoder = $jwtTokenEncoder;
        $this->trustedTokenLifetime = $trustedTokenLifetime;
    }

    public function generateToken(string $username, string $firewall, int $version): TrustedDeviceToken
    {
        $validUntil = $this->getValidUntil();
        $jwtToken = $this->jwtTokenEncoder->generateToken($username, $firewall, $version, $validUntil);

        return new TrustedDeviceToken($jwtToken);
    }

    public function decodeToken(string $trustedTokenEncoded): ?TrustedDeviceToken
    {
        $jwtToken = $this->jwtTokenEncoder->decodeToken($trustedTokenEncoded);
        if (!$jwtToken instanceof Plain) {
            return null;
        }

        return new TrustedDeviceToken($jwtToken);
    }

    private function getValidUntil(): \DateTimeImmutable
    {
        return $this->getDateTimeNow()->add(new \DateInterval('PT'.$this->trustedTokenLifetime.'S'));
    }

    protected function getDateTimeNow(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }
}
