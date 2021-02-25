<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Http\EventListener;

use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class SuppressRememberMeListener implements EventSubscriberInterface
{
    // Just before Symfony's RememberMeListener
    private const PRIORITY = -63;

    public function onSuccessfulLogin(LoginSuccessEvent $event): void
    {
        $passport = $event->getPassport();
        if (!$passport->hasBadge(RememberMeBadge::class)) {
            return;
        }

        /** @var RememberMeBadge $rememberMeBadge */
        $rememberMeBadge = $passport->getBadge(RememberMeBadge::class);
        if (!$rememberMeBadge->isEnabled()) {
            return; // User did not request a remember-me cookie
        }

        $token = $event->getAuthenticatedToken();
        if (!($token instanceof TwoFactorTokenInterface)) {
            return; // We're not in a 2fa process
        }

        // Disable remember-me cookie
        $rememberMeBadge->disable();
        $token->setAttribute(TwoFactorTokenInterface::ATTRIBUTE_NAME_USE_REMEMBER_ME, true);
    }

    public static function getSubscribedEvents(): array
    {
        return [LoginSuccessEvent::class => ['onSuccessfulLogin', self::PRIORITY]];
    }
}
