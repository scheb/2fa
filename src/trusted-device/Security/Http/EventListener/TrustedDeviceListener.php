<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Http\EventListener;

use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\Http\Authenticator\Passport\Badge\TrustedDeviceBadge;
use Scheb\TwoFactorBundle\Security\Http\Authenticator\Passport\TwoFactorPassport;
use Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\TrustedDeviceManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class TrustedDeviceListener implements EventSubscriberInterface
{
    /**
     * @var TrustedDeviceManagerInterface
     */
    private $trustedDeviceManager;

    public function __construct(TrustedDeviceManagerInterface $trustedDeviceManager)
    {
        $this->trustedDeviceManager = $trustedDeviceManager;
    }

    public function onSuccessfulLogin(LoginSuccessEvent $loginSuccessEvent): void
    {
        if ($loginSuccessEvent->getAuthenticatedToken() instanceof TwoFactorTokenInterface) {
            // Two-factor authentication still not complete
            return;
        }

        $passport = $loginSuccessEvent->getPassport();
        if (!($passport instanceof TwoFactorPassport)) {
            return;
        }

        if (!$passport->hasBadge(TrustedDeviceBadge::class)) {
            return;
        }

        $user = $loginSuccessEvent->getAuthenticatedToken()->getUser();
        $firewallName = $passport->getFirewallName();

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
