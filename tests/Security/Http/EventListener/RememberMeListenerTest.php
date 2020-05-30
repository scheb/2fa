<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\Http\EventListener;

use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Scheb\TwoFactorBundle\Security\Http\Authenticator\Passport\TwoFactorPassport;
use Scheb\TwoFactorBundle\Security\Http\EventListener\RememberMeListener;
use Scheb\TwoFactorBundle\Security\Http\EventListener\TrustedDeviceListener;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class RememberMeListenerTest extends TestCase
{
    /**
     * @var MockObject|Response
     */
    private $response;

    /**
     * @var MockObject|LoginSuccessEvent
     */
    private $loginSuccessEvent;

    /**
     * @var TrustedDeviceListener
     */
    private $rememberMeListener;

    protected function setUp(): void
    {
        $this->requireSymfony5_1();
        $this->response = $this->createMock(Response::class);
        $this->response->headers = $this->createMock(ResponseHeaderBag::class);

        $this->loginSuccessEvent = $this->createMock(LoginSuccessEvent::class);
        $this->rememberMeListener = new RememberMeListener();
    }

    private function stubHasResponse(): void
    {
        $this->loginSuccessEvent
            ->expects($this->any())
            ->method('getResponse')
            ->willReturn($this->response);
    }

    private function stubAuthenticatedToken(TokenInterface $authenticatedToken): void
    {
        $this->loginSuccessEvent
            ->expects($this->any())
            ->method('getAuthenticatedToken')
            ->willReturn($authenticatedToken);
    }

    private function stubPassport(PassportInterface $passport): void
    {
        $this->loginSuccessEvent
            ->expects($this->any())
            ->method('getPassport')
            ->willReturn($passport);
    }

    private function stubPassportHasRememberMeBadge(MockObject $passport): void
    {
        $passport
            ->expects($this->any())
            ->method('hasBadge')
            ->with(RememberMeBadge::class)
            ->willReturn(true);
    }

    /**
     * @return MockObject|TwoFactorTokenInterface
     */
    private function stubTwoFactorTokenWithRememberMeCookie($cookie): MockObject
    {
        $twoFactorToken = $this->createMock(TwoFactorTokenInterface::class);
        $twoFactorToken
            ->expects($this->any())
            ->method('hasAttribute')
            ->with(TwoFactorTokenInterface::ATTRIBUTE_NAME_REMEMBER_ME_COOKIE)
            ->willReturn(true);
        $twoFactorToken
            ->expects($this->any())
            ->method('getAttribute')
            ->with(TwoFactorTokenInterface::ATTRIBUTE_NAME_REMEMBER_ME_COOKIE)
            ->willReturn([$cookie]);

        return $twoFactorToken;
    }

    /**
     * @test
     */
    public function onSuccessfulLogin_isTwoFactorToken_doNothing(): void
    {
        $passport = $this->createMock(TwoFactorPassport::class);
        $authenticatedToken = $this->createMock(TwoFactorTokenInterface::class);

        $this->stubAuthenticatedToken($authenticatedToken);
        $this->stubPassport($passport);
        $this->stubPassportHasRememberMeBadge($passport);
        $this->stubHasResponse();

        $this->response->headers
            ->expects($this->never())
            ->method($this->anything());

        $this->rememberMeListener->onSuccessfulLogin($this->loginSuccessEvent);
    }

    /**
     * @test
     */
    public function onSuccessfulLogin_noTwoFactorPassport(): void
    {
        $passport = $this->createMock(PassportInterface::class);
        $authenticatedToken = $this->createMock(TokenInterface::class);

        $this->stubAuthenticatedToken($authenticatedToken);
        $this->stubPassport($passport);
        $this->stubPassportHasRememberMeBadge($passport);
        $this->stubHasResponse();

        $this->response->headers
            ->expects($this->never())
            ->method($this->anything());

        $this->rememberMeListener->onSuccessfulLogin($this->loginSuccessEvent);
    }

    /**
     * @test
     */
    public function onSuccessfulLogin_noTrustedDeviceBadge_doNothing(): void
    {
        $passport = $this->createMock(TwoFactorPassport::class);
        $authenticatedToken = $this->createMock(TokenInterface::class);

        $this->stubAuthenticatedToken($authenticatedToken);
        $this->stubPassport($passport);
        $this->stubHasResponse();

        $this->response->headers
            ->expects($this->never())
            ->method($this->anything());

        $this->rememberMeListener->onSuccessfulLogin($this->loginSuccessEvent);
    }

    /**
     * @test
     */
    public function onSuccessfulLogin_tokenHasRememberMeCookie_setCookie(): void
    {
        $cookie = $this->createMock(Cookie::class);
        $passport = $this->createMock(TwoFactorPassport::class);
        $authenticatedToken = $this->createMock(TokenInterface::class);

        $this->stubAuthenticatedToken($authenticatedToken);
        $this->stubPassport($passport);
        $this->stubPassportHasRememberMeBadge($passport);
        $this->stubHasResponse();

        $twoFactorToken = $this->stubTwoFactorTokenWithRememberMeCookie($cookie);
        $passport
            ->expects($this->once())
            ->method('getTwoFactorToken')
            ->willReturn($twoFactorToken);

        $this->response->headers
            ->expects($this->once())
            ->method('setCookie')
            ->with($cookie);

        $this->rememberMeListener->onSuccessfulLogin($this->loginSuccessEvent);
    }
}
