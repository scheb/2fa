<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Http\Firewall;

use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\Authorization\TwoFactorAccessDecider;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvent;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvents;
use Scheb\TwoFactorBundle\Security\TwoFactor\TwoFactorFirewallConfig;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Firewall\AbstractListener;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Handles access control in the "2fa in progress" phase.
 */
class TwoFactorAccessListener extends AbstractListener implements FirewallListenerInterface
{
    /**
     * @var TwoFactorFirewallConfig
     */
    private $twoFactorFirewallConfig;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var TwoFactorAccessDecider
     */
    private $twoFactorAccessDecider;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(
        TwoFactorFirewallConfig $twoFactorFirewallConfig,
        TokenStorageInterface $tokenStorage,
        TwoFactorAccessDecider $twoFactorAccessDecider,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->twoFactorFirewallConfig = $twoFactorFirewallConfig;
        $this->tokenStorage = $tokenStorage;
        $this->twoFactorAccessDecider = $twoFactorAccessDecider;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function supports(Request $request): ?bool
    {
        $token = $this->tokenStorage->getToken();

        // No need to check for firewall name here, the listener is bound to the firewall context
        return $token instanceof TwoFactorTokenInterface;
    }

    public function authenticate(RequestEvent $requestEvent): void
    {
        /** @var TwoFactorTokenInterface $token */
        $token = $this->tokenStorage->getToken();
        $request = $requestEvent->getRequest();
        if ($this->twoFactorFirewallConfig->isCheckPathRequest($request)) {
            return;
        }

        if ($this->twoFactorFirewallConfig->isAuthFormRequest($request)) {
            $event = new TwoFactorAuthenticationEvent($request, $token);
            $this->eventDispatcher->dispatch($event, TwoFactorAuthenticationEvents::FORM);

            return;
        }

        if (!$this->twoFactorAccessDecider->isAccessible($request, $token)) {
            $exception = new AccessDeniedException('User is in a two-factor authentication process.');
            $exception->setSubject($request);

            throw $exception;
        }
    }

    public static function getPriority(): int
    {
        // When the class is injected via FirewallListenerFactoryInterface
        // Inject before Symfony's AccessListener (-255) and after the LogoutListener (-127)
        return -191;
    }
}
