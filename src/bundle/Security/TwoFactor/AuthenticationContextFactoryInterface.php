<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

interface AuthenticationContextFactoryInterface
{
    public function create(Request $request, TokenInterface $token, Passport $passport, string $firewallName): AuthenticationContextInterface;
}
