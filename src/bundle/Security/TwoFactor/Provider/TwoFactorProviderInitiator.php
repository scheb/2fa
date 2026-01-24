<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Provider;

use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenFactoryInterface;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Exception\UnknownTwoFactorProviderException;
use function array_walk;
use function count;
use function method_exists;
use function trigger_error;
use const E_USER_DEPRECATED;

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

    public function beginTwoFactorAuthentication(AuthenticationContextInterface $context): TwoFactorTokenInterface|null
    {
        $activeTwoFactorProviders = $statelessProviders = [];

        // Iterate over two-factor providers and begin the two-factor authentication process.
        foreach ($this->providerRegistry->getAllProviders() as $providerName => $provider) {
            if (!$provider->beginAuthentication($context)) {
                continue;
            }

            $activeTwoFactorProviders[] = $providerName;

            if (!method_exists($provider, 'needsPreparation')) {
                @trigger_error(
                    'Two-factor provider "'.$providerName.'" does not implement needsPreparation() method. This method will be required in the next major version.',
                    E_USER_DEPRECATED,
                );
            }

            if (!method_exists($provider, 'needsPreparation') || $provider->needsPreparation()) {
                continue;
            }

            $statelessProviders[] = $providerName;
        }

        if (0 === count($activeTwoFactorProviders)) {
            return null;
        }

        $authenticatedToken = $context->getToken();
        $twoFactorToken = $this->twoFactorTokenFactory->create($authenticatedToken, $context->getFirewallName(), $activeTwoFactorProviders);

        array_walk($statelessProviders, static fn (string $providerName) => $twoFactorToken->setTwoFactorProviderPrepared($providerName));

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
}
