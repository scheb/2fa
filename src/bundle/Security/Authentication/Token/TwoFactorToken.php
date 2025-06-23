<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Authentication\Token;

use Deprecated;
use InvalidArgumentException;
use LogicException;
use RuntimeException;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Exception\UnknownTwoFactorProviderException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use function array_key_exists;
use function array_keys;
use function array_search;
use function array_unshift;
use function count;
use function reset;
use function sprintf;

/**
 * @api Part of the bundle's public API, may be extended
 */
class TwoFactorToken implements TwoFactorTokenInterface
{
    /** @var array<string,mixed> */
    private array $attributes = [];

    /** @var bool[] */
    private array $preparedProviders = [];

    /**
     * @param string[] $twoFactorProviders
     */
    public function __construct(
        private TokenInterface $authenticatedToken,
        private string|null $credentials,
        private string $firewallName,
        private array $twoFactorProviders,
    ) {
        if (null === $authenticatedToken->getUser()) {
            throw new InvalidArgumentException('The authenticated token must have a user object set.');
        }
    }

    public function getUser(): UserInterface
    {
        $user = $this->authenticatedToken->getUser();
        if (null === $user) {
            throw new RuntimeException('The authenticated token must have a user object set, though null was returned.');
        }

        return $user;
    }

    public function setUser(UserInterface $user): void
    {
        $this->authenticatedToken->setUser($user);
    }

    public function getUserIdentifier(): string
    {
        return $this->authenticatedToken->getUserIdentifier();
    }

    /**
     * {@inheritDoc}
     */
    public function getRoleNames(): array
    {
        return [];
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

    public function getCredentials(): string|null
    {
        return $this->credentials;
    }

    #[Deprecated]
    public function eraseCredentials(): void
    {
        $this->credentials = null;
    }

    public function getAuthenticatedToken(): TokenInterface
    {
        return $this->authenticatedToken;
    }

    /**
     * {@inheritDoc}
     */
    public function getTwoFactorProviders(): array
    {
        return $this->twoFactorProviders;
    }

    public function preferTwoFactorProvider(string $preferredProvider): void
    {
        $this->removeTwoFactorProvider($preferredProvider);
        array_unshift($this->twoFactorProviders, $preferredProvider);
    }

    public function getCurrentTwoFactorProvider(): string|null
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
            throw new LogicException(sprintf('Two-factor provider "%s" cannot be completed because it was not prepared.', $providerName));
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
        return 0 === count($this->twoFactorProviders);
    }

    public function getFirewallName(): string
    {
        return $this->firewallName;
    }

    /**
     * @return mixed[]
     */
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

    /**
     * @param mixed[] $data
     */
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

    /**
     * @return array<mixed>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @param array<mixed> $attributes
     */
    public function setAttributes(array $attributes): void
    {
        $this->attributes = $attributes;
    }

    public function hasAttribute(string $name): bool
    {
        return array_key_exists($name, $this->attributes);
    }

    public function getAttribute(string $name): mixed
    {
        if (!array_key_exists($name, $this->attributes)) {
            throw new InvalidArgumentException(sprintf('This token has no "%s" attribute.', $name));
        }

        return $this->attributes[$name];
    }

    public function setAttribute(string $name, mixed $value): void
    {
        $this->attributes[$name] = $value;
    }

    public function __toString(): string
    {
        return $this->getUserIdentifier();
    }
}
