<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google;

use OTPHP\TOTP;
use OTPHP\TOTPInterface;
use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Exception\TwoFactorProviderLogicException;
use function strlen;

/**
 * @final
 */
class GoogleTotpFactory
{
    public function __construct(
        private ?string $server,
        private ?string $issuer,
        private int $digits,
    ) {
    }

    public function createTotpForUser(TwoFactorInterface $user): TOTPInterface
    {
        $secret = $user->getGoogleAuthenticatorSecret();
        if (null === $secret || 0 === strlen($secret)) {
            throw new TwoFactorProviderLogicException('Cannot initialize TOTP, no secret code provided.');
        }

        /** @psalm-suppress ArgumentTypeCoercion */
        $totp = TOTP::create($secret, 30, 'sha1', $this->digits);

        /** @psalm-suppress RiskyTruthyFalsyComparison */
        $userAndHost = $user->getGoogleAuthenticatorUsername().($this->server ? '@'.$this->server : '');
        $totp->setLabel($userAndHost);

        if (null !== $this->issuer && $this->issuer) {
            $totp->setIssuer($this->issuer);
        }

        return $totp;
    }
}
