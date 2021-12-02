<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Authentication\Token;

use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Exception\UnknownTwoFactorProviderException;
use Scheb\TwoFactorBundle\Security\UsernameHelper;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class TwoFactorToken implements TwoFactorTokenInterface
{
    /**
     * @var TokenInterface
     */
    private $authenticatedToken;

    /**
     * @var string|null
     */
    private $credentials;

    /**
     * @var string
     */
    private $firewallName;

    /**
     * @var array
     */
    private $attributes = [];

    /**
     * @var string[]
     */
    private $twoFactorProviders;

    /**
     * @var bool[]
     */
    private $preparedProviders = [];

    public function __construct(TokenInterface $authenticatedToken, ?string $credentials, string $firewallName, array $twoFactorProviders)
    {
        $this->authenticatedToken = $authenticatedToken;
        $this->credentials = $credentials;
        $this->firewallName = $firewallName;
        $this->twoFactorProviders = $twoFactorProviders;
    }

    public function getUser()
    {
        return $this->authenticatedToken->getUser();
    }

    /**
     * @param string|\Stringable|UserInterface $user
     */
    public function setUser($user): void
    {
        /** @psalm-suppress PossiblyInvalidArgument */
        $this->authenticatedToken->setUser($user);
    }

    public function getUserIdentifier(): string
    {
        return UsernameHelper::getTokenUsername($this->authenticatedToken);
    }

    public function getUsername(): string
    {
        return $this->getUserIdentifier();
    }

    // Compatibility for Symfony < 5.0
    public function getRoles(): array
    {
        return [];
    }

    // Compatibility for Symfony >= 4.3
    public function getRoleNames(): array
    {
        return $this->getRoles();
    }

    public function createWithCredentials(string $credentials): TwoFactorTokenInterface
    {
        $credentialsToken = new self($this->authenticatedToken, $credentials, $this->firewallName, $this->twoFactorProviders);
        foreach (array_keys($this->preparedProviders) as $preparedProviderName) {
            $credentialsToken->setTwoFactorProviderPrepared($preparedProviderName);
        }
        $credentialsToken->setAttributes($this->getAttributes());

        return $credentialsToken;
    }

    public function getCredentials(): ?string
    {
        return $this->credentials;
    }

    public function eraseCredentials(): void
    {
        $this->credentials = null;
    }

    public function getAuthenticatedToken(): TokenInterface
    {
        return $this->authenticatedToken;
    }

    public function getTwoFactorProviders(): array
    {
        return $this->twoFactorProviders;
    }

    public function preferTwoFactorProvider(string $preferredProvider): void
    {
        $this->removeTwoFactorProvider($preferredProvider);
        array_unshift($this->twoFactorProviders, $preferredProvider);
    }

    public function getCurrentTwoFactorProvider(): ?string
    {
        $first = reset($this->twoFactorProviders);

        return false !== $first ? $first : null;
    }

    public function isTwoFactorProviderPrepared(string $providerName): bool
    {
        return $this->preparedProviders[$providerName] ?? false;
    }

    public function setTwoFactorProviderPrepared(string $providerName): void
    {
        $this->preparedProviders[$providerName] = true;
    }

    public function setTwoFactorProviderComplete(string $providerName): void
    {
        if (!$this->isTwoFactorProviderPrepared($providerName)) {
            throw new \LogicException(sprintf('Two-factor provider "%s" cannot be completed because it was not prepared.', $providerName));
        }

        $this->removeTwoFactorProvider($providerName);
    }

    private function removeTwoFactorProvider(string $providerName): void
    {
        $key = array_search($providerName, $this->twoFactorProviders, true);
        if (false === $key) {
            throw new UnknownTwoFactorProviderException(sprintf('Two-factor provider "%s" is not active.', $providerName));
        }
        unset($this->twoFactorProviders[$key]);
    }

    public function allTwoFactorProvidersAuthenticated(): bool
    {
        return 0 === \count($this->twoFactorProviders);
    }

    /**
     * @deprecated since 5.8, use getFirewallName() instead
     */
    public function getProviderKey(): string
    {
        if (1 !== \func_num_args() || true !== func_get_arg(0)) {
            @trigger_error(sprintf('Method "%s" is deprecated, use "getFirewallName()" instead.', __METHOD__), E_USER_DEPRECATED);
        }

        return $this->firewallName;
    }

    public function getFirewallName(): string
    {
        return $this->getProviderKey(true);
    }

    public function isAuthenticated(): bool
    {
        return true;
    }

    /**
     * @param bool $isAuthenticated
     */
    public function setAuthenticated($isAuthenticated): void
    {
        throw new \RuntimeException('Cannot change authenticated once initialized.');
    }

    public function __serialize(): array
    {
        return [
            $this->authenticatedToken,
            $this->credentials,
            $this->firewallName,
            $this->attributes,
            $this->twoFactorProviders,
            $this->preparedProviders,
        ];
    }

    // Compatibility for Symfony 4.4 & PHP < 7.4
    public function serialize(): string
    {
        return serialize($this->__serialize());
    }

    public function __unserialize(array $data): void
    {
        [
            $this->authenticatedToken,
            $this->credentials,
            $this->firewallName,
            $this->attributes,
            $this->twoFactorProviders,
            $this->preparedProviders,
        ] = $data;
    }

    // Compatibility for Symfony 4.4 & PHP < 7.4
    public function unserialize($serialized): void
    {
        $this->__unserialize(unserialize($serialized));
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function setAttributes(array $attributes): void
    {
        $this->attributes = $attributes;
    }

    /**
     * @param string $name
     */
    public function hasAttribute($name): bool
    {
        return \array_key_exists($name, $this->attributes);
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function getAttribute($name)
    {
        if (!\array_key_exists($name, $this->attributes)) {
            throw new \InvalidArgumentException(sprintf('This token has no "%s" attribute.', $name));
        }

        return $this->attributes[$name];
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function setAttribute($name, $value): void
    {
        $this->attributes[$name] = $value;
    }

    public function __toString(): string
    {
        return $this->getUsername();
    }
}
