<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google;

use OTPHP\TOTP;
use OTPHP\TOTPInterface;
use Psr\Clock\ClockInterface;
use ReflectionClass;
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

        if ((new ReflectionClass(TOTP::class))->hasProperty('clock')) {
            /** @psalm-suppress ArgumentTypeCoercion */
            $totp = TOTP::create($secret, 30, 'sha1', $this->digits, clock: $this->clock);
        } else {
            /** @psalm-suppress ArgumentTypeCoercion */
            $totp = TOTP::create($secret, 30, 'sha1', $this->digits);
        }

        $userAndHost = $user->getGoogleAuthenticatorUsername().(null !== $this->server && $this->server ? '@'.$this->server : '');
        $totp->setLabel($userAndHost);

        if (null !== $this->issuer && $this->issuer) {
            $totp->setIssuer($this->issuer);
        }

        return $totp;
    }
}
