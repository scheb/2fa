<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Http\Firewall;

use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\Authorization\TwoFactorAccessDecider;
use Scheb\TwoFactorBundle\Security\TwoFactor\TwoFactorFirewallConfig;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Firewall\AbstractListener;

/**
 * Handles access control in the "2fa in progress" phase.
 *
 * @final
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

    public function __construct(
        TwoFactorFirewallConfig $twoFactorFirewallConfig,
        TokenStorageInterface $tokenStorage,
        TwoFactorAccessDecider $twoFactorAccessDecider
    ) {
        $this->twoFactorFirewallConfig = $twoFactorFirewallConfig;
        $this->tokenStorage = $tokenStorage;
        $this->twoFactorAccessDecider = $twoFactorAccessDecider;
    }

    public function supports(Request $request): ?bool
    {
        // When the path is explicitly configured for anonymous access, no need to check access (important for lazy
        // firewalls, to prevent the response cache control to be flagged "private")
        return !$this->twoFactorAccessDecider->isPubliclyAccessible($request);
    }

    public function authenticate(RequestEvent $event): void
    {
        // When the firewall is lazy, the token is not initialized in the "supports" stage, so this check does only work
        // within the "authenticate" stage.
        $token = $this->tokenStorage->getToken();
        if (!($token instanceof TwoFactorTokenInterface)) {
            // No need to check for firewall name here, the listener is bound to the firewall context
            return;
        }

        /** @var TwoFactorTokenInterface $token */
        $token = $this->tokenStorage->getToken();
        $request = $event->getRequest();
        if ($this->twoFactorFirewallConfig->isCheckPathRequest($request)) {
            return;
        }

        if ($this->twoFactorFirewallConfig->isAuthFormRequest($request)) {
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
