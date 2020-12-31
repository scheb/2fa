<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Authentication\Exception;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * @final
 */
class TwoFactorProviderNotFoundException extends AuthenticationException
{
    public const MESSAGE_KEY = 'Two-factor provider not found.';

    /**
     * @var string|null
     */
    private $provider;

    public function getMessageKey(): string
    {
        return self::MESSAGE_KEY;
    }

    public function getProvider(): ?string
    {
        return $this->provider;
    }

    public function setProvider(string $provider): void
    {
        $this->provider = $provider;
    }

    public function getMessageData(): array
    {
        return ['{{ provider }}' => $this->provider];
    }

    public function __serialize(): array
    {
        return [$this->provider, parent::__serialize()];
    }

    public function __unserialize(array $data): void
    {
        [$this->provider, $parentData] = $data;
        parent::__unserialize($parentData);
    }
}
