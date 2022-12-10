<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp;

use OTPHP\TOTP;
use OTPHP\TOTPInterface;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Exception\TwoFactorProviderLogicException;
use function strlen;

/**
 * @final
 */
class TotpFactory
{
    /**
     * @param array<string,mixed> $customParameters
     */
    public function __construct(
        private ?string $server,
        private ?string $issuer,
        private array $customParameters,
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
            $totpConfiguration->getDigits()
        );

        $userAndHost = $user->getTotpAuthenticationUsername().($this->server ? '@'.$this->server : '');
        $totp->setLabel($userAndHost);

        if ($this->issuer) {
            $totp->setIssuer($this->issuer);
        }

        foreach ($this->customParameters as $key => $value) {
            $totp->setParameter($key, $value);
        }

        return $totp;
    }
}
