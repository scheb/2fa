<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Trusted;

use DateInterval;
use DateTimeImmutable;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use function preg_match;
use function substr_count;

/**
 * @final
 */
class TrustedCookieResponseListener implements EventSubscriberInterface
{
    /**
     * @param Cookie::SAMESITE_*|null $cookieSameSite
     */
    public function __construct(
        private TrustedDeviceTokenStorage $trustedTokenStorage,
        private int $trustedTokenLifetime,
        private string $cookieName,
        private bool|null $cookieSecure,
        private string|null $cookieSameSite,
        private string|null $cookiePath,
        private string|null $cookieDomain,
    ) {
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$this->trustedTokenStorage->hasUpdatedCookie()) {
            return;
        }

        $domain = null;

        if (null !== $this->cookieDomain) {
            $domain = $this->cookieDomain;
        } else {
            $requestHost = $event->getRequest()->getHost();
            if ($this->shouldSetDomain($requestHost)) {
                $domain = '.'.$requestHost;
            }
        }

        // Set the cookie
        $cookie = new Cookie(
            $this->cookieName,
            $this->trustedTokenStorage->getCookieValue(),
            $this->getValidUntil(),
            $this->cookiePath,
            $domain,
            null === $this->cookieSecure ? $event->getRequest()->isSecure() : $this->cookieSecure,
            true,
            false,
            $this->cookieSameSite,
        );

        $response = $event->getResponse();
        $response->headers->setCookie($cookie);
    }

    private function shouldSetDomain(string $requestHost): bool
    {
        return !(
            'localhost' === $requestHost
            || preg_match('#^(?:[0-9]{1,3}\.){3}[0-9]{1,3}$#', $requestHost) // IPv4
            || substr_count($requestHost, ':') > 1 // IPv6
        );
    }

    private function getValidUntil(): DateTimeImmutable
    {
        return $this->getDateTimeNow()->add(new DateInterval('PT'.$this->trustedTokenLifetime.'S'));
    }

    protected function getDateTimeNow(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::RESPONSE => 'onKernelResponse'];
    }
}
