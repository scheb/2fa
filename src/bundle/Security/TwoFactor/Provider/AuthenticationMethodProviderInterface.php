<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Provider;

/**
 * A two-factor provider that can name the authentication method it verifies.
 *
 * Symfony 8.2 records on the security token which methods the user proved and when, as the "amr"
 * values of RFC 8176 (see Symfony's AuthenticationMethod constants), so that a trust resolver
 * can require a specific one. A provider implementing this interface has its proof recorded
 * under that method instead of an unspecified one.
 */
interface AuthenticationMethodProviderInterface
{
    /**
     * Returns an "amr" value of RFC 8176, such as "otp" for a one-time password.
     */
    public function getAuthenticationMethod(): string;
}
