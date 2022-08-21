<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Trusted;

use DateTimeImmutable;
use Lcobucci\Clock\Clock;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Exception;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Validation\Constraint;

/**
 * @internal
 *
 * @final
 */
class JwtTokenEncoder
{
    public const CLAIM_USERNAME = 'usr';
    public const CLAIM_FIREWALL = 'fwl';
    public const CLAIM_VERSION = 'vsn';

    private Configuration $configuration;
    private Clock $clock;

    public function __construct(string $applicationSecret, ?Clock $clock = null)
    {
        $this->configuration = Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText($applicationSecret));
        $this->clock = $clock ?? SystemClock::fromSystemTimezone();
    }

    public function generateToken(string $username, string $firewallName, int $version, DateTimeImmutable $validUntil): Plain
    {
        $builder = $this->configuration->builder()
            ->issuedAt($this->clock->now())
            ->expiresAt($validUntil)
            ->withClaim(self::CLAIM_USERNAME, $username)
            ->withClaim(self::CLAIM_FIREWALL, $firewallName)
            ->withClaim(self::CLAIM_VERSION, $version);

        return $builder->getToken($this->configuration->signer(), $this->configuration->signingKey());
    }

    public function decodeToken(string $token): ?Plain
    {
        try {
            $token = $this->configuration->parser()->parse($token);
        } catch (Exception) {
            return null; // Could not decode token
        }

        if (!$token instanceof Plain) {
            return null;
        }

        if (!$this->configuration->validator()->validate($token, ...$this->validationConstraints())) {
            return null;
        }

        return $token;
    }

    /** @return iterable<int, Constraint> */
    private function validationConstraints(): iterable
    {
        yield new Constraint\SignedWith($this->configuration->signer(), $this->configuration->signingKey());
        yield new Constraint\ValidAt($this->clock); // replace with LooseValidAt once dependency on lcobucci/jwt is bumped up
        yield from $this->configuration->validationConstraints();
    }
}
