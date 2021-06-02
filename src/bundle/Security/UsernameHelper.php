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
        /** @psalm-suppress RedundantCastGivenDocblockType */

        return (string) $token->getUsername();
    }

    public static function getUserUsername(UserInterface $user): string
    {
        // Compatibility with Symfony >= 5.3
        if (method_exists($user, 'getUserIdentifier')) {
            return $user->getUserIdentifier();
        }

        // Compatibility with Symfony <= 5.2
        /** @psalm-suppress RedundantCastGivenDocblockType */

        return (string) $user->getUsername();
    }
}
