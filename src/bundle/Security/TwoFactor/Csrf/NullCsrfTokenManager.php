<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2020 Christian Scheb
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

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
