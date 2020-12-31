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
    /**
     * @param string $tokenId
     */
    public function getToken($tokenId): CsrfToken
    {
        return new CsrfToken($tokenId, '');
    }

    /**
     * @param string $tokenId
     */
    public function refreshToken($tokenId): CsrfToken
    {
        return new CsrfToken($tokenId, '');
    }

    /**
     * @param string $tokenId
     */
    public function removeToken($tokenId): ?string
    {
        return null;
    }

    public function isTokenValid(CsrfToken $token): bool
    {
        return true;
    }
}
