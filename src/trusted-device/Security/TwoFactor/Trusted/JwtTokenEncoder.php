<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Trusted;

use DateTimeImmutable;
use Lcobucci\Clock\Clock;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Exception;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint;
use function strlen;

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

    private Clock $clock;

    public function __construct(private Configuration $configuration, ?Clock $clock = null)
    {
        $this->clock = $clock ?? SystemClock::fromSystemTimezone();
    }

    public function generateToken(string $username, string $firewallName, int $version, DateTimeImmutable $validUntil): UnencryptedToken
    {
        $builder = $this->configuration->builder()
            ->issuedAt($this->clock->now())
            ->expiresAt($validUntil)
            ->withClaim(self::CLAIM_USERNAME, $username)
            ->withClaim(self::CLAIM_FIREWALL, $firewallName)
            ->withClaim(self::CLAIM_VERSION, $version);

        return $builder->getToken($this->configuration->signer(), $this->configuration->signingKey());
    }

    public function decodeToken(string $encodedToken): ?Plain
    {
        if (0 === strlen($encodedToken)) {
            return null;
        }

        try {
            /** @var non-empty-string $encodedToken */
            $token = $this->configuration->parser()->parse($encodedToken);
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
        yield new Constraint\LooseValidAt($this->clock);
        yield from $this->configuration->validationConstraints();
    }
}
