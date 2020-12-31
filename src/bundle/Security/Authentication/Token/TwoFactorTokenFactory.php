<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Authentication\Token;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @final
 */
class TwoFactorTokenFactory implements TwoFactorTokenFactoryInterface
{
    public function create(TokenInterface $authenticatedToken, string $providerKey, array $twoFactorProviders): TwoFactorTokenInterface
    {
        return new TwoFactorToken($authenticatedToken, null, $providerKey, $twoFactorProviders);
    }
}
