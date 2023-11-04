<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Condition;

use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use function in_array;

/**
 * @final
 */
class AuthenticatedTokenCondition implements TwoFactorConditionInterface
{
    /**
     * @param string[] $supportedTokens
     */
    public function __construct(private readonly array $supportedTokens)
    {
    }

    public function shouldPerformTwoFactorAuthentication(AuthenticationContextInterface $context): bool
    {
        $token = $context->getToken();

        return in_array($token::class, $this->supportedTokens, true);
    }
}
