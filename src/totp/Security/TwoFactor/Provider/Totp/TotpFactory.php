<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp;

use OTPHP\TOTP;
use OTPHP\TOTPInterface;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface;

/**
 * @final
 */
class TotpFactory
{
    /**
     * @var string|null
     */
    private $server;

    /**
     * @var string|null
     */
    private $issuer;

    /**
     * @var string[]
     */
    private $customParameters;

    public function __construct(?string $server, ?string $issuer, array $customParameters)
    {
        $this->server = $server;
        $this->issuer = $issuer;
        $this->customParameters = $customParameters;
    }

    public function createTotpForUser(TwoFactorInterface $user): TOTPInterface
    {
        $totpConfiguration = $user->getTotpAuthenticationConfiguration();
        if (null === $totpConfiguration) {
            throw new \RuntimeException('Cannot create TOTP, no TotpAuthenticationConfiguration provided.');
        }

        $totp = TOTP::create(
            $totpConfiguration->getSecret(),
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
