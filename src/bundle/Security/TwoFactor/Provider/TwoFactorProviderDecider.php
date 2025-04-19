<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Provider;

use Scheb\TwoFactorBundle\Model\PreferredProviderInterface;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;

/**
 * @api Part of the bundle's public API, may be extended
 */
class TwoFactorProviderDecider implements TwoFactorProviderDeciderInterface
{
    /**
     * @param string[] $activeProviders
     */
    public function getPreferredTwoFactorProvider(array $activeProviders, TwoFactorTokenInterface $token, AuthenticationContextInterface $context): string|null
    {
        $user = $context->getUser();

        if ($user instanceof PreferredProviderInterface) {
            return $user->getPreferredTwoFactorProvider();
        }

        return null;
    }
}
