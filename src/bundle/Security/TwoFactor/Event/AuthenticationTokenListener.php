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
    public function __construct(private string $firewallName, private AuthenticationHandlerInterface $twoFactorAuthenticationHandler, private AuthenticationContextFactoryInterface $authenticationContextFactory, private RequestStack $requestStack)
    {
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
        $request = $this->requestStack->getMainRequest();
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
