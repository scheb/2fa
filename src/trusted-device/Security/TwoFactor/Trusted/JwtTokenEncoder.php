<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Trusted;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Exception;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Validation\Constraint\SignedWith;

/**
 * @final
 *
 * @internal
 */
class JwtTokenEncoder
{
    public const CLAIM_USERNAME = 'usr';
    public const CLAIM_FIREWALL = 'fwl';
    public const CLAIM_VERSION = 'vsn';

    /**
     * @var Configuration
     */
    private $configuration;

    public function __construct(string $applicationSecret)
    {
        $this->configuration = Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText($applicationSecret));
        $this->configuration->setValidationConstraints(new SignedWith($this->configuration->signer(), $this->configuration->signingKey()));
    }

    public function generateToken(string $username, string $firewallName, int $version, \DateTimeImmutable $validUntil): Plain
    {
        $builder = $this->configuration->builder()
            ->issuedAt(new \DateTimeImmutable())
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
        } catch (Exception $e) {
            return null; // Could not decode token
        }

        if (!$token instanceof Plain) {
            return null;
        }

        if (!$this->configuration->validator()->validate($token, ...$this->configuration->validationConstraints())) {
            return null;
        }

        if ($token->isExpired(new \DateTimeImmutable())) {
            return null;
        }

        return $token;
    }
}
