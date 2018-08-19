<?php

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp;

use OTPHP\TOTP;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface;

class TotpAuthenticator implements TotpAuthenticatorInterface
{
    /**
     * @var TotpFactoryInterface
     */
    private $totpFactory;

    /**
     * @var string
     */
    private $qrCodeGenerator;

    /**
     * @var string
     */
    private $qrCodeDataPlaceholder;

    /**
     * @param TotpFactoryInterface $totpFactory
     * @param string               $qrCodeGenerator
     * @param string               $qrCodeDataPlaceholder
     */
    public function __construct(TotpFactoryInterface $totpFactory, string $qrCodeGenerator, string $qrCodeDataPlaceholder)
    {
        $this->qrCodeGenerator = $qrCodeGenerator;
        $this->qrCodeDataPlaceholder = $qrCodeDataPlaceholder;
        $this->totpFactory = $totpFactory;
    }

    public function checkCode(TwoFactorInterface $user, string $code): bool
    {
        $totp = $this->totpFactory->getTotpForUser($user);

        return $totp->verify($code);
    }

    public function getUrl(TOTP $totp): string
    {
        return $totp->getQrCodeUri($this->qrCodeGenerator, $this->qrCodeDataPlaceholder);
    }
}
