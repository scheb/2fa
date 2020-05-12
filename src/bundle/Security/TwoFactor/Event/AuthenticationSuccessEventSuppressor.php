<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Event;

use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\AuthenticationEvents;
use Symfony\Component\Security\Core\Event\AuthenticationEvent;

class AuthenticationSuccessEventSuppressor implements EventSubscriberInterface
{
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
            // Must trigger after TwoFactorProviderPreparationListener::onLogin to stop event propagation immediately
            AuthenticationEvents::AUTHENTICATION_SUCCESS => ['onLogin', PHP_INT_MAX - 1],
        ];
    }
}
