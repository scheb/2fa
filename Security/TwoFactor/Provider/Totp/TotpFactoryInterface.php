<?php

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp;

use OTPHP\TOTP;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface;

interface TotpFactoryInterface
{
    /**
     * @return TOTP
     */
    public function generateNewTotp(): TOTP;

    /**
     * @param TwoFactorInterface $user
     *
     * @return TOTP
     */
    public function getTotpForUser(TwoFactorInterface $user): TOTP;
}
