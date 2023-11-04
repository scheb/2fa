<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Trusted;

use DateInterval;
use DateTimeImmutable;
use Lcobucci\JWT\Token\Plain;
use function strlen;

/**
 * @final
 */
class TrustedDeviceTokenEncoder
{
    public function __construct(
        private readonly JwtTokenEncoder $jwtTokenEncoder,
        private readonly int $trustedTokenLifetime,
    ) {
    }

    public function generateToken(string $username, string $firewall, int $version): TrustedDeviceToken
    {
        $validUntil = $this->getValidUntil();
        $jwtToken = $this->jwtTokenEncoder->generateToken($username, $firewall, $version, $validUntil);

        return new TrustedDeviceToken($jwtToken);
    }

    public function decodeToken(string $trustedTokenEncoded): TrustedDeviceToken|null
    {
        if (0 === strlen($trustedTokenEncoded)) {
            return null;
        }

        /** @var non-empty-string $trustedTokenEncoded */
        $jwtToken = $this->jwtTokenEncoder->decodeToken($trustedTokenEncoded);
        if (!$jwtToken instanceof Plain) {
            return null;
        }

        return new TrustedDeviceToken($jwtToken);
    }

    private function getValidUntil(): DateTimeImmutable
    {
        return $this->getDateTimeNow()->add(new DateInterval('PT'.$this->trustedTokenLifetime.'S'));
    }

    protected function getDateTimeNow(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }
}
