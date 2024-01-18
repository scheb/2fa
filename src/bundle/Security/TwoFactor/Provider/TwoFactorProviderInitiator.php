<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Provider;

use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenFactoryInterface;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Exception\UnknownTwoFactorProviderException;

/**
 * @final
 */
class TwoFactorProviderInitiator
{
    public function __construct(
        private readonly TwoFactorProviderRegistry $providerRegistry,
        private readonly TwoFactorTokenFactoryInterface $twoFactorTokenFactory,
        private readonly TwoFactorProviderDeciderInterface $twoFactorProviderDecider,
    ) {
    }

    /**
     * @return string[]
     */
    private function getActiveTwoFactorProviders(AuthenticationContextInterface $context): array
    {
        $activeTwoFactorProviders = [];

        // Iterate over two-factor providers and begin the two-factor authentication process.
        foreach ($this->providerRegistry->getAllProviders() as $providerName => $provider) {
            if (!$provider->beginAuthentication($context)) {
                continue;
            }

            $activeTwoFactorProviders[] = $providerName;
        }

        return $activeTwoFactorProviders;
    }

    public function beginTwoFactorAuthentication(AuthenticationContextInterface $context): TwoFactorTokenInterface|null
    {
        $activeTwoFactorProviders = $this->getActiveTwoFactorProviders($context);

        $authenticatedToken = $context->getToken();
        if ($activeTwoFactorProviders) {
            $twoFactorToken = $this->twoFactorTokenFactory->create($authenticatedToken, $context->getFirewallName(), $activeTwoFactorProviders);

            $preferredProvider = $this->twoFactorProviderDecider->getPreferredTwoFactorProvider($activeTwoFactorProviders, $twoFactorToken, $context);

            if (null !== $preferredProvider) {
                try {
                    $twoFactorToken->preferTwoFactorProvider($preferredProvider);
                } catch (UnknownTwoFactorProviderException) {
                    // Bad user input
                }
            }

            return $twoFactorToken;
        }

        return null;
    }
}
