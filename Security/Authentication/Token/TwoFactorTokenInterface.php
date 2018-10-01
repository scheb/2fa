<?php

namespace Scheb\TwoFactorBundle\Security\Authentication\Token;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

interface TwoFactorTokenInterface extends TokenInterface
{
    public function getAuthenticatedToken(): TokenInterface;

    public function getTwoFactorProviders(): array;

    public function preferTwoFactorProvider(string $preferredProvider): void;

    public function getCurrentTwoFactorProvider(): ?string;

    public function setTwoFactorProviderComplete(string $providerName): void;

    public function allTwoFactorProvidersAuthenticated(): bool;

    public function getProviderKey(): string;
}
