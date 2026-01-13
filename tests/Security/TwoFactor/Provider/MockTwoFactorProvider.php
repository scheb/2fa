<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Provider;

use RuntimeException;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorFormRendererInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderInterface;

class MockTwoFactorProvider implements TwoFactorProviderInterface
{
    private bool $beginAuthenticationResult = false;
    private int $beginAuthenticationCallCount = 0;
    private AuthenticationContextInterface|null $lastContext = null;

    public function __construct(private readonly bool $needsPreparation = false)
    {
    }

    public function setBeginAuthenticationResult(bool $result): void
    {
        $this->beginAuthenticationResult = $result;
    }

    public function getBeginAuthenticationCallCount(): int
    {
        return $this->beginAuthenticationCallCount;
    }

    public function getLastContext(): AuthenticationContextInterface|null
    {
        return $this->lastContext;
    }

    public function beginAuthentication(AuthenticationContextInterface $context): bool
    {
        ++$this->beginAuthenticationCallCount;
        $this->lastContext = $context;

        return $this->beginAuthenticationResult;
    }

    public function needsPreparation(): bool
    {
        return $this->needsPreparation;
    }

    public function prepareAuthentication(object $user): void
    {
        // Mock implementation
    }

    public function validateAuthenticationCode(object $user, string $authenticationCode): bool
    {
        return false;
    }

    public function getFormRenderer(): TwoFactorFormRendererInterface
    {
        throw new RuntimeException('Not implemented in mock');
    }
}
