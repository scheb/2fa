<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;

final class ThrottlingListener implements EventSubscriberInterface
{
    public function __construct(
        private RateLimiterFactory $rateLimiterFactory
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TwoFactorAuthenticationEvents::ATTEMPT => 'onTwoFactorAttempt',
            TwoFactorAuthenticationEvents::SUCCESS => 'onTwoFactorSuccess',
        ];
    }

    public function onTwoFactorAttempt(TwoFactorAuthenticationEvent $event): void
    {
        $rateLimiter = $this->rateLimiterFactory->create($event->getRequest()->getClientIp());

        if (!$rateLimiter->consume()->isAccepted()) {
            throw new TooManyRequestsHttpException();
        }
    }

    public function onTwoFactorSuccess(TwoFactorAuthenticationEvent $event): void
    {
        $rateLimiter = $this->rateLimiterFactory->create($event->getRequest()->getClientIp());
        $rateLimiter->reset();
    }
}
