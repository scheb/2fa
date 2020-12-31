<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\QrCode;

use Endroid\QrCode\QrCode;
use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface as GoogleAuthenticatorTwoFactorInterface;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface as TotpTwoFactorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;

/**
 * @final
 */
class QrCodeGenerator
{
    /**
     * @var GoogleAuthenticatorInterface|null
     */
    private $googleAuthenticator;

    /**
     * @var TotpAuthenticatorInterface|null
     */
    private $totpAuthenticator;

    public function __construct(?GoogleAuthenticatorInterface $googleAuthenticator, ?TotpAuthenticatorInterface $totpAuthenticator)
    {
        $this->googleAuthenticator = $googleAuthenticator;
        $this->totpAuthenticator = $totpAuthenticator;
    }

    public function getGoogleAuthenticatorQrCode(GoogleAuthenticatorTwoFactorInterface $user): QrCode
    {
        if (null === $this->googleAuthenticator) {
            throw new \RuntimeException('You don\'t have GoogleAuthenticator support enabled. Please install scheb/2fa-google-authenticator and activate it in the configuration.');
        }

        return new QrCode($this->googleAuthenticator->getQRContent($user));
    }

    public function getTotpQrCode(TotpTwoFactorInterface $user): QrCode
    {
        if (null === $this->totpAuthenticator) {
            throw new \RuntimeException('You don\'t have TOTP support enabled. Please install scheb/2fa-totp and activate it in the configuration.');
        }

        return new QrCode($this->totpAuthenticator->getQRContent($user));
    }
}
