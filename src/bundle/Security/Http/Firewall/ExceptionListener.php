<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Http\Firewall;

use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\Http\Authentication\AuthenticationRequiredHandlerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvent;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @final
 */
class ExceptionListener implements EventSubscriberInterface
{
    // Just before the firewall's Symfony\Component\Security\Http\Firewall\ExceptionListener
    private const LISTENER_PRIORITY = 2;

    /**
     * @var string
     */
    private $firewallName;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var AuthenticationRequiredHandlerInterface
     */
    private $authenticationRequiredHandler;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(
        string $firewallName,
        TokenStorageInterface $tokenStorage,
        AuthenticationRequiredHandlerInterface $authenticationRequiredHandler,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->firewallName = $firewallName;
        $this->tokenStorage = $tokenStorage;
        $this->authenticationRequiredHandler = $authenticationRequiredHandler;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        do {
            if ($exception instanceof AccessDeniedException) {
                $this->handleAccessDeniedException($event);

                return;
            }
        } while (null !== $exception = $exception->getPrevious());
    }

    private function handleAccessDeniedException(ExceptionEvent $exceptionEvent): void
    {
        $token = $this->tokenStorage->getToken();
        if (!($token instanceof TwoFactorTokenInterface && $token->getProviderKey(true) === $this->firewallName)) {
            return;
        }

        /** @var TwoFactorTokenInterface $token */
        $request = $exceptionEvent->getRequest();

        $event = new TwoFactorAuthenticationEvent($request, $token);
        $this->eventDispatcher->dispatch($event, TwoFactorAuthenticationEvents::REQUIRE);

        $response = $this->authenticationRequiredHandler->onAuthenticationRequired($request, $token);
        $exceptionEvent->allowCustomResponseCode();
        $exceptionEvent->setResponse($response);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', self::LISTENER_PRIORITY],
        ];
    }
}
