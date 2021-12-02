<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Authentication\Token;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

interface TwoFactorTokenFactoryInterface
{
    /**
     * Create a new TwoFactorToken.
     *
     * @param string   $firewallName       The firewall name
     * @param string[] $twoFactorProviders The two-factor provider aliases, which are currently available
     */
    public function create(TokenInterface $authenticatedToken, string $firewallName, array $twoFactorProviders): TwoFactorTokenInterface;
}
