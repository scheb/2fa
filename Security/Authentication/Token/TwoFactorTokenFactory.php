<?php

namespace Scheb\TwoFactorBundle\Security\Authentication\Token;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class TwoFactorTokenFactory implements TwoFactorTokenFactoryInterface
{
    /**
     * @var string
     */
    private $twoFactorTokenClass;

    public function __construct(string $twoFactorTokenClass)
    {
        $this->twoFactorTokenClass = $twoFactorTokenClass;
    }

    public function create(TokenInterface $authenticatedToken, ?string $credentials, string $providerKey, array $twoFactorProviders): TwoFactorTokenInterface
    {
        return new $this->twoFactorTokenClass($authenticatedToken, $credentials, $providerKey, $twoFactorProviders);
    }
}
