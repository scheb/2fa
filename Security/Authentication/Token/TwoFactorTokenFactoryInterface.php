<?php

namespace Scheb\TwoFactorBundle\Security\Authentication\Token;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

interface TwoFactorTokenFactoryInterface
{
    /**
     * Create a new TwoFactorToken.
     *
     * @param TokenInterface $authenticatedToken
     * @param null|string    $credentials        The two-factor authentication code or null
     * @param string         $providerKey        The firewall name
     * @param string[]       $twoFactorProviders The two-factor provider aliases, which are currently available
     *
     * @return TwoFactorTokenInterface
     */
    public function create(TokenInterface $authenticatedToken, ?string $credentials, string $providerKey, array $twoFactorProviders): TwoFactorTokenInterface;
}
