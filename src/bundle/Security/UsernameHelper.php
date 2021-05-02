<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @final
 *
 * @internal
 *
 * Handles compatibility with different Symfony versions
 */
class UsernameHelper
{
    public static function getTokenUsername(TokenInterface $token): string
    {
        // Compatibility with Symfony >= 5.3
        if (method_exists($token, 'getUserIdentifier')) {
            return $token->getUserIdentifier();
        }

        // Compatibility with Symfony <= 5.2
        if (method_exists($token, 'getUsername')) {
            return (string) $token->getUsername();
        }

        throw new \RuntimeException('Security token did not provide a "getUsername" or "getUserIdentifier" method.');
    }

    public static function getUserUsername(UserInterface $user): string
    {
        // Compatibility with Symfony >= 5.3
        if (method_exists($user, 'getUserIdentifier')) {
            return $user->getUserIdentifier();
        }

        // Compatibility with Symfony <= 5.2
        if (method_exists($user, 'getUsername')) {
            return (string) $user->getUsername();
        }

        throw new \RuntimeException('User entity did not provide a "getUsername" or "getUserIdentifier" method.');
    }
}
