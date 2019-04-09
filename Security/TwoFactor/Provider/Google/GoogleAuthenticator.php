<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google;

use ParagonIE\ConstantTime\Base32;
use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface;

class GoogleAuthenticator implements GoogleAuthenticatorInterface
{
    /**
     * @var GoogleTotpFactory
     */
    private $totpFactory;

    public function __construct(GoogleTotpFactory $totpFactory)
    {
        $this->totpFactory = $totpFactory;
    }

    public function checkCode(TwoFactorInterface $user, string $code): bool
    {
        // Strip any user added spaces
        $code = str_replace(' ', '', $code);

        return $this->totpFactory->createTotp($user)->verify($code);
    }

    public function getQRContent(TwoFactorInterface $user): string
    {
        return $this->totpFactory->createTotp($user)->getProvisioningUri();
    }

    public function generateSecret(): string
    {
        return trim(Base32::encodeUpper(random_bytes(32)), '=');
    }
}
