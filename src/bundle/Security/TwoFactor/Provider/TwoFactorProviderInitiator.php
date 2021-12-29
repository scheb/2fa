<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Provider;

use Scheb\TwoFactorBundle\Model\PreferredProviderInterface;
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
        private TwoFactorProviderRegistry $providerRegistry,
        private TwoFactorTokenFactoryInterface $twoFactorTokenFactory
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

    public function beginTwoFactorAuthentication(AuthenticationContextInterface $context): ?TwoFactorTokenInterface
    {
        $activeTwoFactorProviders = $this->getActiveTwoFactorProviders($context);

        $authenticatedToken = $context->getToken();
        if ($activeTwoFactorProviders) {
            $twoFactorToken = $this->twoFactorTokenFactory->create($authenticatedToken, $context->getFirewallName(), $activeTwoFactorProviders);
            $this->setPreferredProvider($twoFactorToken, $context->getUser()); // Prioritize the user's preferred provider

            return $twoFactorToken;
        }

        return null;
    }

    private function setPreferredProvider(TwoFactorTokenInterface $token, object $user): void
    {
        if (!($user instanceof PreferredProviderInterface)) {
            return;
        }

        $preferredProvider = $user->getPreferredTwoFactorProvider();
        if (!$preferredProvider) {
            return;
        }

        try {
            $token->preferTwoFactorProvider($preferredProvider);
        } catch (UnknownTwoFactorProviderException) {
            // Bad user input
        }
    }
}
