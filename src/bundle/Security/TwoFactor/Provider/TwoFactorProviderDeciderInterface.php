<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Provider;

use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;

interface TwoFactorProviderDeciderInterface
{
    /**
     * Return the alias of the preferred two-factor provider.
     *
     * @param string[] $activeProviders
     */
    public function getPreferredTwoFactorProvider(array $activeProviders, TwoFactorTokenInterface $token, object $user): string|null;
}
