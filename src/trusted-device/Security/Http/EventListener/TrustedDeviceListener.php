<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Http\EventListener;

use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\Http\Authenticator\Passport\Badge\TrustedDeviceBadge;
use Scheb\TwoFactorBundle\Security\Http\Authenticator\Passport\Credentials\TwoFactorCodeCredentials;
use Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\TrustedDeviceManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

/**
 * @final
 */
class TrustedDeviceListener implements EventSubscriberInterface
{
    public function __construct(private TrustedDeviceManagerInterface $trustedDeviceManager)
    {
    }

    public function onSuccessfulLogin(LoginSuccessEvent $loginSuccessEvent): void
    {
        if ($loginSuccessEvent->getAuthenticatedToken() instanceof TwoFactorTokenInterface) {
            // Two-factor authentication still not complete
            return;
        }

        $passport = $loginSuccessEvent->getPassport();
        if (!$passport->hasBadge(TwoFactorCodeCredentials::class)) {
            return;
        }

        if (!$passport->hasBadge(TrustedDeviceBadge::class)) {
            return;
        }

        $user = $loginSuccessEvent->getAuthenticatedToken()->getUser();
        $firewallName = $loginSuccessEvent->getFirewallName();

        if ($this->trustedDeviceManager->canSetTrustedDevice($user, $loginSuccessEvent->getRequest(), $firewallName)) {
            $this->trustedDeviceManager->addTrustedDevice($user, $firewallName);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onSuccessfulLogin',
        ];
    }
}
