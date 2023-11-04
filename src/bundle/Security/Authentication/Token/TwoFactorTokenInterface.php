<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Authentication\Token;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

interface TwoFactorTokenInterface extends TokenInterface
{
    /**
     * @internal
     */
    public const ATTRIBUTE_NAME_USE_REMEMBER_ME = 'use_remember_me';

    /**
     * Return the authenticated token.
     */
    public function getAuthenticatedToken(): TokenInterface;

    /**
     * Returns a user representation.
     *
     * For a TwoFactorToken this is not nullable.
     */
    public function getUser(): UserInterface;

    /**
     * Duplicate the token with credentials.
     */
    public function createWithCredentials(string $credentials): self;

    /**
     * Return the firewall name.
     */
    public function getFirewallName(): string;

    /**
     * Return list of two-factor providers (their aliases), which are available.
     *
     * @return string[]
     */
    public function getTwoFactorProviders(): array;

    /**
     * Change the current two-factor provider. Provider alias is passed as an argument.
     */
    public function preferTwoFactorProvider(string $preferredProvider): void;

    /**
     * Return the alias of the two-factor provider, which is currently active.
     */
    public function getCurrentTwoFactorProvider(): string|null;

    /**
     * Flag a two-factor provider as complete. The provider's alias is passed as the argument.
     */
    public function setTwoFactorProviderComplete(string $providerName): void;

    /**
     * Check if all two-factor providers have been completed.
     */
    public function allTwoFactorProvidersAuthenticated(): bool;

    /**
     * Check if a two-factor provider has completed preparation. The provider's alias is passed as the argument.
     */
    public function isTwoFactorProviderPrepared(string $providerName): bool;

    /**
     * Remember when a two-factor provider has completed preparation. The provider's alias is passed as the argument.
     */
    public function setTwoFactorProviderPrepared(string $providerName): void;
}
