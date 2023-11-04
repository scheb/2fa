<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Csrf;

use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @final
 */
class NullCsrfTokenManager implements CsrfTokenManagerInterface
{
    public function getToken(string $tokenId): CsrfToken
    {
        return new CsrfToken($tokenId, '');
    }

    public function refreshToken(string $tokenId): CsrfToken
    {
        return new CsrfToken($tokenId, '');
    }

    public function removeToken(string $tokenId): string|null
    {
        return null;
    }

    public function isTokenValid(CsrfToken $token): bool
    {
        return true;
    }
}
