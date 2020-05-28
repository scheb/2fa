<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Provider;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Uses the security token to store if a two-factor provider has been prepared.
 */
class TokenPreparationRecorder implements PreparationRecorderInterface
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    public function isTwoFactorProviderPrepared(string $firewallName, string $providerName): bool
    {
        $token = $this->tokenStorage->getToken();
        if (!($token instanceof PreparationRecorderInterface)) {
            throw new \RuntimeException('The security token has to be an instance of PreparationRecorderInterface.');
        }

        return $token->isTwoFactorProviderPrepared($firewallName, $providerName);
    }

    public function setTwoFactorProviderPrepared(string $firewallName, string $providerName): void
    {
        $token = $this->tokenStorage->getToken();
        if (!($token instanceof PreparationRecorderInterface)) {
            throw new \RuntimeException('The security token has to be an instance of PreparationRecorderInterface.');
        }

        $token->setTwoFactorProviderPrepared($firewallName, $providerName);
    }
}
