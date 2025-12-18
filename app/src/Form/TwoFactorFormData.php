<?php

declare(strict_types=1);

namespace App\Form;

use Scheb\TwoFactorBundle\Security\TwoFactor\Validator\Constraints\UserGoogleTotpCode;
use Scheb\TwoFactorBundle\Security\TwoFactor\Validator\Constraints\UserTotpCode;

class TwoFactorFormData
{
    #[UserGoogleTotpCode]
    public string $googleTotpCode;

    #[UserTotpCode]
    public string $totpCode;
}
