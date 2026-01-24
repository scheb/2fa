<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google;

use OTPHP\TOTP;
use OTPHP\TOTPInterface;
use Psr\Clock\ClockInterface;
use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Exception\TwoFactorProviderLogicException;
use function strlen;

/**
 * @final
 */
class GoogleTotpFactory
{
    public function __construct(
        private readonly string|null $server,
        private readonly string|null $issuer,
        private readonly int $digits,
        private readonly ClockInterface|null $clock = null,
    ) {
    }

    public function createTotpForUser(TwoFactorInterface $user): TOTPInterface
    {
        $secret = $user->getGoogleAuthenticatorSecret();
        if (null === $secret || 0 === strlen($secret)) {
            throw new TwoFactorProviderLogicException('Cannot initialize TOTP, no secret code provided.');
        }

        /** @psalm-suppress ArgumentTypeCoercion */
        $totp = TOTP::create($secret, 30, 'sha1', $this->digits, clock: $this->clock);

        $usernameLabel = $user->getGoogleAuthenticatorUsername() ?? '';
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

        return $totp;
    }
}
