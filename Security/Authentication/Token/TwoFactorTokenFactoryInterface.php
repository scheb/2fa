<?php

namespace Scheb\TwoFactorBundle\Security\Authentication\Token;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

interface TwoFactorTokenFactoryInterface
{
    public function create(TokenInterface $authenticatedToken, ?string $credentials, string $providerKey, array $twoFactorProviders): TwoFactorTokenInterface;
}
