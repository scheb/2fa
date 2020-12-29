<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Trusted;

use Lcobucci\JWT\Token\Plain;

class TrustedDeviceToken
{
    /**
     * @var Plain
     */
    private $jwtToken;

    public function __construct(Plain $jwtToken)
    {
        $this->jwtToken = $jwtToken;
    }

    public function authenticatesRealm(string $username, string $firewallName): bool
    {
        return $this->jwtToken->claims()->get(JwtTokenEncoder::CLAIM_USERNAME) === $username
            && $this->jwtToken->claims()->get(JwtTokenEncoder::CLAIM_FIREWALL) === $firewallName;
    }

    public function versionMatches(int $version): bool
    {
        return $this->jwtToken->claims()->get(JwtTokenEncoder::CLAIM_VERSION) === $version;
    }

    public function isExpired(): bool
    {
        return $this->jwtToken->isExpired(new \DateTimeImmutable());
    }

    public function serialize(): string
    {
        return $this->jwtToken->toString();
    }
}
