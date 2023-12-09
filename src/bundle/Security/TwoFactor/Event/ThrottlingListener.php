<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RateLimiter\RequestRateLimiterInterface;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

/**
 * @final
 */
class ThrottlingListener implements EventSubscriberInterface
{
    public function __construct(
        private RequestRateLimiterInterface $requestRateLimiter,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            TwoFactorAuthenticationEvents::ATTEMPT => 'onTwoFactorAttempt',
            TwoFactorAuthenticationEvents::SUCCESS => 'onTwoFactorSuccess',
        ];
    }

    public function onTwoFactorAttempt(TwoFactorAuthenticationEvent $event): void
    {
        if (!$this->requestRateLimiter->consume($event->getRequest())->isAccepted()) {
            throw new TooManyRequestsHttpException();
        }
    }

    public function onTwoFactorSuccess(TwoFactorAuthenticationEvent $event): void
    {
        $this->requestRateLimiter->reset($event->getRequest());
    }
}
