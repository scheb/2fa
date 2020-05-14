<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Event;

use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderPreparationListener;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\AuthenticationEvents;
use Symfony\Component\Security\Core\Event\AuthenticationEvent;

class AuthenticationSuccessEventSuppressor implements EventSubscriberInterface
{
    // Must trigger after TwoFactorProviderPreparationListener::onLogin to stop event propagation immediately
    public const LISTENER_PRIORITY = TwoFactorProviderPreparationListener::LISTENER_PRIORITY - 1;

    /**
     * @var string
     */
    private $firewallName;

    public function __construct(string $firewallName)
    {
        $this->firewallName = $firewallName;
    }

    public function onLogin(AuthenticationEvent $event): void
    {
        $token = $event->getAuthenticationToken();

        // We have a TwoFactorToken, make sure the security.authentication.success is not propagated to other
        // listeners, since we do not have a successful login (yet)
        if ($this->isTwoFactorTokenAndFirewall($token)) {
            $event->stopPropagation();
        }
    }

    private function isTwoFactorTokenAndFirewall(TokenInterface $token): bool
    {
        return $token instanceof TwoFactorTokenInterface && $token->getProviderKey() === $this->firewallName;
    }

    public static function getSubscribedEvents()
    {
        return [
            AuthenticationEvents::AUTHENTICATION_SUCCESS => ['onLogin', self::LISTENER_PRIORITY],
        ];
    }
}
