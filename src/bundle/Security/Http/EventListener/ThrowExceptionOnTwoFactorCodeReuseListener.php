<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Http\EventListener;

use Scheb\TwoFactorBundle\Security\Authentication\Exception\ReusedTwoFactorCodeException;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvents;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorCodeReusedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @final
 */
class ThrowExceptionOnTwoFactorCodeReuseListener implements EventSubscriberInterface
{
    public const LISTENER_PRIORITY = -256;

    public function handle(TwoFactorCodeReusedEvent $event): void
    {
        throw new ReusedTwoFactorCodeException();
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [TwoFactorAuthenticationEvents::CODE_REUSED => ['handle', self::LISTENER_PRIORITY]];
    }
}
