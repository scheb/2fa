<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Handler;

use Scheb\TwoFactorBundle\Model\PreferredProviderInterface;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenFactoryInterface;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Exception\UnknownTwoFactorProviderException;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderRegistry;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @final
 */
class TwoFactorProviderHandler implements AuthenticationHandlerInterface
{
    /**
     * @var TwoFactorProviderRegistry
     */
    private $providerRegistry;

    /**
     * @var TwoFactorTokenFactoryInterface
     */
    private $twoFactorTokenFactory;

    public function __construct(TwoFactorProviderRegistry $providerRegistry, TwoFactorTokenFactoryInterface $twoFactorTokenFactory)
    {
        $this->providerRegistry = $providerRegistry;
        $this->twoFactorTokenFactory = $twoFactorTokenFactory;
    }

    private function getActiveTwoFactorProviders(AuthenticationContextInterface $context): array
    {
        $activeTwoFactorProviders = [];

        // Iterate over two-factor providers and begin the two-factor authentication process.
        foreach ($this->providerRegistry->getAllProviders() as $providerName => $provider) {
            if ($provider->beginAuthentication($context)) {
                $activeTwoFactorProviders[] = $providerName;
            }
        }

        return $activeTwoFactorProviders;
    }

    public function beginTwoFactorAuthentication(AuthenticationContextInterface $context): TokenInterface
    {
        $activeTwoFactorProviders = $this->getActiveTwoFactorProviders($context);

        $authenticatedToken = $context->getToken();
        if ($activeTwoFactorProviders) {
            $twoFactorToken = $this->twoFactorTokenFactory->create($authenticatedToken, $context->getFirewallName(), $activeTwoFactorProviders);
            $this->setPreferredProvider($twoFactorToken, $context->getUser()); // Prioritize the user's preferred provider

            return $twoFactorToken;
        }

        return $authenticatedToken;
    }

    /**
     * @param string|object $user
     */
    private function setPreferredProvider(TwoFactorTokenInterface $token, $user): void
    {
        if ($user instanceof PreferredProviderInterface) {
            if ($preferredProvider = $user->getPreferredTwoFactorProvider()) {
                try {
                    $token->preferTwoFactorProvider($preferredProvider);
                } catch (UnknownTwoFactorProviderException $e) {
                    // Bad user input
                }
            }
        }
    }
}
