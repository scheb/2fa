<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Trusted;

use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\TrustedCookieResponseListener;
use Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\TrustedDeviceTokenStorage;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class TrustedCookieResponseListenerTest extends TestCase
{
    /**
     * @var MockObject|TrustedDeviceTokenStorage
     */
    private $trustedTokenStorage;

    /**
     * @var TrustedCookieResponseListener
     */
    private $cookieResponseListener;

    /**
     * @var Response
     */
    private $response;

    protected function setUp(): void
    {
        $this->trustedTokenStorage = $this->createMock(TrustedDeviceTokenStorage::class);
        $this->cookieResponseListener = $this->createTrustedCookieResponseListener(null);
        $this->response = new Response();
    }

    private function createTrustedCookieResponseListener(?string $domain = null): TrustedCookieResponseListener
    {
        $cookieResponseListener = new TestableTrustedCookieResponseListener(
            $this->trustedTokenStorage,
            3600,
            'cookieName',
            true,
            Cookie::SAMESITE_LAX,
            '/cookie-path',
            $domain
        );
        $cookieResponseListener->now = new \DateTime('2018-01-01 00:00:00');

        return $cookieResponseListener;
    }

    /**
     * @param string $host
     *
     * @return MockObject|Request
     */
    private function createRequestWithHost(string $host = 'example.org'): MockObject
    {
        $request = $this->createMock(Request::class);
        $request
            ->expects($this->any())
            ->method('getHost')
            ->willReturn($host);

        return $request;
    }

    /**
     * @param MockObject $request
     *
     * @return MockObject|FilterResponseEvent|RequestEvent
     */
    private function createEventWithRequest(MockObject $request)
    {
        // Symfony < 4.3
        if (!class_exists(RequestEvent::class)) {
            $event = $this->createMock(FilterResponseEvent::class);
        } else {
            $event = $this->createMock(RequestEvent::class);
        }

        $event
            ->expects($this->any())
            ->method('getRequest')
            ->willReturn($request);
        $event
            ->expects($this->any())
            ->method('getResponse')
            ->willReturn($this->response);

        return $event;
    }

    /**
     * @return MockObject|FilterResponseEvent|RequestEvent
     */
    private function createEvent(): MockObject
    {
        $request = $this->createRequestWithHost();

        return $this->createEventWithRequest($request);
    }

    /**
     * @test
     */
    public function onKernelResponse_noUpdatedCookie_noCookieHeader(): void
    {
        $this->trustedTokenStorage
            ->expects($this->once())
            ->method('hasUpdatedCookie')
            ->willReturn(false);

        $event = $this->createEvent();
        $this->cookieResponseListener->onKernelResponse($event);
        $this->assertCount(0, $this->response->headers->getCookies(), 'Response must have no cookie set.');
    }

    /**
     * @test
     */
    public function onKernelResponse_hasUpdatedCookie_addCookieHeader(): void
    {
        $this->trustedTokenStorage
            ->expects($this->once())
            ->method('hasUpdatedCookie')
            ->willReturn(true);

        $this->trustedTokenStorage
            ->expects($this->once())
            ->method('getCookieValue')
            ->willReturn('cookieValue');

        $event = $this->createEvent();
        $this->cookieResponseListener->onKernelResponse($event);
        $cookies = $this->response->headers->getCookies();
        $this->assertCount(1, $cookies, 'Response must have a cookie set.');

        $expectedCookie = new Cookie(
            'cookieName',
            'cookieValue',
            new \DateTime('2018-01-01 01:00:00'),
            '/cookie-path',
            '.example.org',
            true,
            true,
            false,
            Cookie::SAMESITE_LAX
        );
        $this->assertEquals($expectedCookie, $cookies[0]);
    }

    /**
     * @test
     */
    public function onKernelResponse_hasDomainConfigured_setCookieDomain(): void
    {
        $this->cookieResponseListener = $this->createTrustedCookieResponseListener('.different-domain.com');

        $this->trustedTokenStorage
            ->expects($this->once())
            ->method('hasUpdatedCookie')
            ->willReturn(true);

        $event = $this->createEvent();
        $this->cookieResponseListener->onKernelResponse($event);
        $cookies = $this->response->headers->getCookies();
        $this->assertCount(1, $cookies, 'Response must have a cookie set.');

        $expectedCookie = new Cookie(
            'cookieName',
            null,
            new \DateTime('2018-01-01 01:00:00'),
            '/cookie-path',
            '.different-domain.com',
            true,
            true,
            false,
            Cookie::SAMESITE_LAX
        );
        $this->assertEquals($expectedCookie, $cookies[0]);
    }

    /**
     * @test
     * @dataProvider provideRequestHostName
     */
    public function onKernelResponse_excludedHostNames_notSetDomain(string $requestHostName): void
    {
        $this->trustedTokenStorage
            ->expects($this->any())
            ->method('hasUpdatedCookie')
            ->willReturn(true);

        $this->trustedTokenStorage
            ->expects($this->any())
            ->method('getCookieValue')
            ->willReturn('cookieValue');

        $request = $this->createRequestWithHost($requestHostName);
        $event = $this->createEventWithRequest($request);
        $this->cookieResponseListener->onKernelResponse($event);
        $cookies = $this->response->headers->getCookies();
        $this->assertCount(1, $cookies, 'Response must have a cookie set.');
        $this->assertNull($cookies[0]->getDomain());
    }

    public function provideRequestHostName(): array
    {
        return [
            ['localhost'],
            ['123.0.0.1'],
            ['2001:0db8:85a3:0000:0000:8a2e:0370:7334'],
        ];
    }
}

// Make the current DateTime testable
class TestableTrustedCookieResponseListener extends TrustedCookieResponseListener
{
    public $now;

    protected function getDateTimeNow(): \DateTime
    {
        return $this->now;
    }
}
