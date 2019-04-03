<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Authentication\Token;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class TwoFactorTokenFactory implements TwoFactorTokenFactoryInterface
{
    public function create(TokenInterface $authenticatedToken, ?string $credentials, string $providerKey, array $twoFactorProviders): TwoFactorTokenInterface
    {
        return new TwoFactorToken($authenticatedToken, $credentials, $providerKey, $twoFactorProviders);
    }
}
