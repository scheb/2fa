<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Provider;

use Scheb\TwoFactorBundle\Model\PreferredProviderInterface;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;

class TwoFactorProviderDecider implements TwoFactorProviderDeciderInterface
{
    /**
     * @param string[] $activeProviders
     */
    public function getPreferredTwoFactorProvider(array $activeProviders, TwoFactorTokenInterface $token, object $user): string|null
    {
        if ($user instanceof PreferredProviderInterface) {
            return $user->getPreferredTwoFactorProvider();
        }

        return null;
    }
}
