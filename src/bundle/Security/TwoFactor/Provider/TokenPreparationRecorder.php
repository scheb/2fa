<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Provider;

use LogicException;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Exception\UnexpectedTokenException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use function sprintf;

/**
 * Uses the security token to store if a two-factor provider has been prepared.
 *
 * @final
 */
class TokenPreparationRecorder implements PreparationRecorderInterface
{
    public function __construct(private readonly TokenStorageInterface $tokenStorage)
    {
    }

    public function isTwoFactorProviderPrepared(string $firewallName, string $providerName): bool
    {
        $token = $this->tokenStorage->getToken();
        if (!($token instanceof TwoFactorTokenInterface)) {
            throw new UnexpectedTokenException('The security token has to be an instance of TwoFactorTokenInterface.');
        }

        $providerKey = $token->getFirewallName();
        if ($providerKey !== $firewallName) {
            throw new LogicException(sprintf('Cannot store preparation state for firewall "%s" in a TwoFactorToken belonging to "%s".', $firewallName, $providerKey));
        }

        return $token->isTwoFactorProviderPrepared($providerName);
    }

    public function setTwoFactorProviderPrepared(string $firewallName, string $providerName): void
    {
        $token = $this->tokenStorage->getToken();
        if (!($token instanceof TwoFactorTokenInterface)) {
            throw new UnexpectedTokenException('The security token has to be an instance of TwoFactorTokenInterface.');
        }

        $providerKey = $token->getFirewallName();
        if ($providerKey !== $firewallName) {
            throw new LogicException(sprintf('Cannot store preparation state for firewall "%s" in a TwoFactorToken belonging to "%s".', $firewallName, $providerKey));
        }

        $token->setTwoFactorProviderPrepared($providerName);
    }
}
