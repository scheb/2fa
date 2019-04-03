<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Csrf;

use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class NullCsrfTokenManager implements CsrfTokenManagerInterface
{
    public function getToken($tokenId)
    {
        return new CsrfToken($tokenId, '');
    }

    public function refreshToken($tokenId)
    {
        return new CsrfToken($tokenId, '');
    }

    public function removeToken($tokenId)
    {
    }

    public function isTokenValid(CsrfToken $token)
    {
        return true;
    }
}
