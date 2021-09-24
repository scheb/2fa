<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Trusted;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @final
 */
class TrustedCookieResponseListener implements EventSubscriberInterface
{
    /**
     * @var TrustedDeviceTokenStorage
     */
    private $trustedTokenStorage;

    /**
     * @var int
     */
    private $trustedTokenLifetime;

    /**
     * @var string
     */
    private $cookieName;

    /**
     * @var bool|null
     */
    private $cookieSecure;

    /**
     * @var string|null
     */
    private $cookieSameSite;

    /**
     * @var string|null
     */
    private $cookiePath;

    /**
     * @var string|null
     */
    private $cookieDomain;

    public function __construct(
        TrustedDeviceTokenStorage $trustedTokenStorage,
        int $trustedTokenLifetime,
        string $cookieName,
        ?bool $cookieSecure,
        ?string $cookieSameSite,
        ?string $cookiePath,
        ?string $cookieDomain
    ) {
        $this->trustedTokenStorage = $trustedTokenStorage;
        $this->trustedTokenLifetime = $trustedTokenLifetime;
        $this->cookieName = $cookieName;
        $this->cookieSecure = $cookieSecure;
        $this->cookieSameSite = $cookieSameSite;
        $this->cookiePath = $cookiePath;
        $this->cookieDomain = $cookieDomain;
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if ($this->trustedTokenStorage->hasUpdatedCookie()) {
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
                $this->cookieSameSite
            );

            $response = $event->getResponse();
            $response->headers->setCookie($cookie);
        }
    }

    private function shouldSetDomain(string $requestHost): bool
    {
        return !(
            'localhost' === $requestHost
            || preg_match('#^(?:[0-9]{1,3}\.){3}[0-9]{1,3}$#', $requestHost) // IPv4
            || substr_count($requestHost, ':') > 1 // IPv6
        );
    }

    private function getValidUntil(): \DateTime
    {
        return $this->getDateTimeNow()->add(new \DateInterval('PT'.$this->trustedTokenLifetime.'S'));
    }

    protected function getDateTimeNow(): \DateTime
    {
        return new \DateTime();
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }
}
