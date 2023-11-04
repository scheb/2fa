<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use function assert;

/**
 * @final
 */
class AuthenticationContextFactory implements AuthenticationContextFactoryInterface
{
    public function __construct(private readonly string $authenticationContextClass)
    {
    }

    public function create(Request $request, TokenInterface $token, Passport $passport, string $firewallName): AuthenticationContextInterface
    {
        /**
         * @psalm-suppress InvalidStringClass
         */
        $authenticationContext = new $this->authenticationContextClass($request, $token, $passport, $firewallName);
        assert($authenticationContext instanceof AuthenticationContextInterface);

        return $authenticationContext;
    }
}
