<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Trusted;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class TrustedCookieResponseListener
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
     * @var bool
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
        bool $cookieSecure,
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

    /**
     * @param $event FilterResponseEvent|ResponseEvent
     */
    public function onKernelResponse($event): void
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
                $this->cookieSecure,
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
}
