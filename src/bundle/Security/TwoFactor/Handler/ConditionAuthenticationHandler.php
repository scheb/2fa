<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Handler;

use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Condition\TwoFactorConditionInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @final
 */
class ConditionAuthenticationHandler implements AuthenticationHandlerInterface
{
    /**
     * @var AuthenticationHandlerInterface
     */
    private $authenticationHandler;

    /**
     * @var TwoFactorConditionInterface
     */
    private $condition;

    public function __construct(AuthenticationHandlerInterface $authenticationHandler, TwoFactorConditionInterface $condition)
    {
        $this->authenticationHandler = $authenticationHandler;
        $this->condition = $condition;
    }

    public function beginTwoFactorAuthentication(AuthenticationContextInterface $context): TokenInterface
    {
        if (!$this->condition->shouldPerformTwoFactorAuthentication($context)) {
            return $context->getToken();
        }

        return $this->authenticationHandler->beginTwoFactorAuthentication($context);
    }
}
