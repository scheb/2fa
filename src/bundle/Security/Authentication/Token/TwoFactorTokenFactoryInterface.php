<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Authentication\Token;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

interface TwoFactorTokenFactoryInterface
{
    /**
     * Create a new TwoFactorToken.
     *
     * @param string|null $credentials                The two-factor authentication code or null
     * @param string      $providerKey                The firewall name
     * @param string[]    $twoFactorProviders         The two-factor provider aliases, which are currently available
     * @param string[]    $preparedTwoFactorProviders The two-factor provider aliases, which are currently prepared
     */
    public function create(
        TokenInterface $authenticatedToken,
        ?string $credentials,
        string $providerKey,
        array $twoFactorProviders,
        array $preparedTwoFactorProviders
    ): TwoFactorTokenInterface;
}
