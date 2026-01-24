<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp;

use OTPHP\TOTP;
use OTPHP\TOTPInterface;
use Psr\Clock\ClockInterface;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Exception\TwoFactorProviderLogicException;
use function strlen;

/**
 * @final
 */
class TotpFactory
{
    /**
     * @param array<non-empty-string,mixed> $customParameters
     */
    public function __construct(
        private readonly string|null $server,
        private readonly string|null $issuer,
        private readonly array $customParameters,
        private readonly ClockInterface|null $clock = null,
    ) {
    }

    public function createTotpForUser(TwoFactorInterface $user): TOTPInterface
    {
        $totpConfiguration = $user->getTotpAuthenticationConfiguration();
        if (null === $totpConfiguration) {
            throw new TwoFactorProviderLogicException('Cannot initialize TOTP, no TotpAuthenticationConfiguration provided.');
        }

        $secret = $totpConfiguration->getSecret();
        if (0 === strlen($secret)) {
            throw new TwoFactorProviderLogicException('Cannot initialize TOTP, no secret code provided.');
        }

        /** @psalm-suppress ArgumentTypeCoercion */
        $totp = TOTP::create(
            $secret,
            $totpConfiguration->getPeriod(),
            $totpConfiguration->getAlgorithm(),
            $totpConfiguration->getDigits(),
            clock: $this->clock,
        );

        $usernameLabel = $user->getTotpAuthenticationUsername() ?? '';
        $serverLabel = $this->server ?? '';
        $userAndHost = $usernameLabel.('' !== $usernameLabel && '' !== $serverLabel ? '@' : '').$serverLabel;
        if ('' !== $userAndHost) {
            $totp->setLabel($userAndHost);
        }

        if (null !== $this->issuer && '' !== $this->issuer) {
            $totp->setIssuer($this->issuer);

            // Omit the issuer parameter, when the issuer is the only value set.
            // Otherwise FreeOTP app will show the issuer name twice.
            if (null === $totp->getLabel()) {
                $totp = $totp->withIssuerIncludedAsParameter(false);
            }
        }

        foreach ($this->customParameters as $key => $value) {
            $totp->setParameter($key, $value);
        }

        return $totp;
    }
}
