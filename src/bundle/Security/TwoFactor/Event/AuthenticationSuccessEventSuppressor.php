<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Event;

use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderPreparationListener;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\AuthenticationEvents;
use Symfony\Component\Security\Core\Event\AuthenticationEvent;

/**
 * @final
 */
class AuthenticationSuccessEventSuppressor implements EventSubscriberInterface
{
    // Must trigger after TwoFactorProviderPreparationListener::onLogin to stop event propagation immediately
    public const LISTENER_PRIORITY = TwoFactorProviderPreparationListener::AUTHENTICATION_SUCCESS_LISTENER_PRIORITY - 1;

    public function onLogin(AuthenticationEvent $event): void
    {
        $token = $event->getAuthenticationToken();

        // We have a TwoFactorToken, make sure the security.authentication.success is not propagated to other
        // listeners, since we do not have a successful login (yet)
        if (!($token instanceof TwoFactorTokenInterface)) {
            return;
        }

        $event->stopPropagation();
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            AuthenticationEvents::AUTHENTICATION_SUCCESS => ['onLogin', self::LISTENER_PRIORITY],
        ];
    }
}
