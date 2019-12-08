<?php

namespace Scheb\TwoFactorBundle\Security\Authentication\Token;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

interface TwoFactorTokenInterface extends TokenInterface
{
    public const ATTRIBUTE_NAME_REMEMBER_ME_COOKIE = 'remember_me_cookie';

    /**
     * Return the authenticated token.
     *
     * @return TokenInterface
     */
    public function getAuthenticatedToken(): TokenInterface;

    /**
     * Return list of two-factor providers (their aliases), which are available.
     *
     * @return string[]
     */
    public function getTwoFactorProviders(): array;

    /**
     * Change the current two-factor provider. Provider alias is passed as an argument.
     *
     * @param string $preferredProvider
     */
    public function preferTwoFactorProvider(string $preferredProvider): void;

    /**
     * Return the alias of the two-factor provider, which is currently active.
     *
     * @return null|string
     */
    public function getCurrentTwoFactorProvider(): ?string;

    /**
     * Flag a two-factor provider as complete. The provider's alias is passed as the argument.
     *
     * @param string $providerName
     */
    public function setTwoFactorProviderComplete(string $providerName): void;

    /**
     * Check if all two-factor providers have been completed.
     *
     * @return bool
     */
    public function allTwoFactorProvidersAuthenticated(): bool;

    /**
     * Return the provider key (firewall name).
     *
     * @return string
     */
    public function getProviderKey(): string;
}
