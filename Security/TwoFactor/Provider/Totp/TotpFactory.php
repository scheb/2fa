<?php

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp;

use OTPHP\Factory;
use OTPHP\TOTP;
use ParagonIE\ConstantTime\Base32;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface;

class TotpFactory implements TotpFactoryInterface
{
    /**
     * @var null|string
     */
    private $issuer;

    /**
     * @var int
     */
    private $period;

    /**
     * @var int
     */
    private $digits;

    /**
     * @var string
     */
    private $digest;

    /**
     * @var array
     */
    private $customParameters;

    /**
     * @param null|string $issuer
     * @param int         $period
     * @param int         $digits
     * @param string      $digest
     * @param array       $customParameters
     */
    public function __construct(?string $issuer, int $period, int $digits, string $digest, array $customParameters)
    {
        $this->issuer = $issuer;
        $this->digits = $digits;
        $this->digest = $digest;
        $this->customParameters = $customParameters;
        $this->period = $period;
    }

    public function generateNewTotp(): TOTP
    {
        $totp = TOTP::create(
            trim(Base32::encodeUpper(random_bytes(32)), '='),
            $this->period,
            $this->digest,
            $this->digits
        );
        if ($this->issuer) {
            $totp->setIssuer($this->issuer);
        }
        foreach ($this->customParameters as $key => $value) {
            $totp->setParameter($key, $value);
        }

        return $totp;
    }

    public function getTotpForUser(TwoFactorInterface $user): TOTP
    {
        $provisioningUri = $user->getTotpAuthenticationProvisioningUri();
        if ($provisioningUri === null) {
            throw new \Exception('No provisioning URI for the given user.');
        }

        return Factory::loadFromProvisioningUri($provisioningUri);
    }
}
