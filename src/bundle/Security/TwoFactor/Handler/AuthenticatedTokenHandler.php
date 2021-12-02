<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Handler;

use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @final
 */
class AuthenticatedTokenHandler implements AuthenticationHandlerInterface
{
    public function __construct(private AuthenticationHandlerInterface $authenticationHandler, private array $supportedTokens)
    {
    }

    public function beginTwoFactorAuthentication(AuthenticationContextInterface $context): TokenInterface
    {
        $token = $context->getToken();

        // Check if the authenticated token is enabled for two-factor authentication
        if ($this->isTwoFactorAuthenticationEnabledForToken($token)) {
            return $this->authenticationHandler->beginTwoFactorAuthentication($context);
        }

        return $token;
    }

    private function isTwoFactorAuthenticationEnabledForToken(TokenInterface $token): bool
    {
        return \in_array($token::class, $this->supportedTokens, true);
    }
}
