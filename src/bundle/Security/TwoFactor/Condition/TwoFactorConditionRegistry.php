<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Condition;

use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvent;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvents;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @final
 */
class TwoFactorConditionRegistry
{
    /**
     * @param TwoFactorConditionInterface[] $conditions
     */
    public function __construct(
        private readonly iterable $conditions,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function shouldPerformTwoFactorAuthentication(AuthenticationContextInterface $context): bool
    {
        foreach ($this->conditions as $condition) {
            if (!$condition->shouldPerformTwoFactorAuthentication($context)) {
                $event = new TwoFactorAuthenticationEvent($context->getRequest(), $context->getToken());
                $this->eventDispatcher->dispatch($event, TwoFactorAuthenticationEvents::SKIPPED);

                return false;
            }
        }

        return true;
    }
}
