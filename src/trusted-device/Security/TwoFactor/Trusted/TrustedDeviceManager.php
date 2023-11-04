<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Trusted;

use Scheb\TwoFactorBundle\Model\TrustedDeviceInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;

class TrustedDeviceManager implements TrustedDeviceManagerInterface
{
    private const DEFAULT_TOKEN_VERSION = 0;

    public function __construct(private TrustedDeviceTokenStorage $trustedTokenStorage)
    {
    }

    public function canSetTrustedDevice(object $user, Request $request, string $firewallName): bool
    {
        return true;
    }

    public function addTrustedDevice(object $user, string $firewallName): void
    {
        if (!($user instanceof UserInterface)) {
            return;
        }

        $username = $user->getUserIdentifier();
        $version = $this->getTrustedTokenVersion($user);
        $this->trustedTokenStorage->addTrustedToken($username, $firewallName, $version);
    }

    public function isTrustedDevice(object $user, string $firewallName): bool
    {
        if (!($user instanceof UserInterface)) {
            return false;
        }

        $username = $user->getUserIdentifier();
        $version = $this->getTrustedTokenVersion($user);

        return $this->trustedTokenStorage->hasTrustedToken($username, $firewallName, $version);
    }

    private function getTrustedTokenVersion(UserInterface $user): int
    {
        if ($user instanceof TrustedDeviceInterface) {
            return $user->getTrustedTokenVersion();
        }

        return self::DEFAULT_TOKEN_VERSION;
    }
}
