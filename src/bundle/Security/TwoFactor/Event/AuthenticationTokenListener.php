<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Event;

use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\Http\Authenticator\TwoFactorAuthenticator;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextFactoryInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Handler\AuthenticationHandlerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Event\AuthenticationTokenCreatedEvent;

/**
 * @final
 */
class AuthenticationTokenListener implements EventSubscriberInterface
{
    /**
     * @var string
     */
    private $firewallName;

    /**
     * @var AuthenticationHandlerInterface
     */
    private $twoFactorAuthenticationHandler;

    /**
     * @var AuthenticationContextFactoryInterface
     */
    private $authenticationContextFactory;

    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(
        string $firewallName,
        AuthenticationHandlerInterface $twoFactorAuthenticationHandler,
        AuthenticationContextFactoryInterface $authenticationContextFactory,
        RequestStack $requestStack
    ) {
        $this->twoFactorAuthenticationHandler = $twoFactorAuthenticationHandler;
        $this->authenticationContextFactory = $authenticationContextFactory;
        $this->requestStack = $requestStack;
        $this->firewallName = $firewallName;
    }

    public function onAuthenticationTokenCreated(AuthenticationTokenCreatedEvent $event): void
    {
        $token = $event->getAuthenticatedToken();

        // TwoFactorTokenInterface can be ignored
        if ($token instanceof TwoFactorTokenInterface) {
            return;
        }

        // The token has already completed 2fa
        if ($token->hasAttribute(TwoFactorAuthenticator::FLAG_2FA_COMPLETE)) {
            return;
        }

        $request = $this->getRequest();
        $context = $this->authenticationContextFactory->create($request, $token, $this->firewallName);

        $newToken = $this->twoFactorAuthenticationHandler->beginTwoFactorAuthentication($context);
        if ($newToken !== $token) {
            $event->setAuthenticatedToken($newToken);
        }
    }

    private function getRequest(): Request
    {
        // Compatibility for Symfony >= 5.3
        if (method_exists(RequestStack::class, 'getMainRequest')) {
            $request = $this->requestStack->getMainRequest();
        } else {
            $request = $this->requestStack->getMasterRequest();
        }
        if (null === $request) {
            throw new \RuntimeException('No request available');
        }

        return $request;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AuthenticationTokenCreatedEvent::class => 'onAuthenticationTokenCreated',
        ];
    }
}
