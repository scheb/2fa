<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\Http\EventListener;

use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\Http\EventListener\SuppressRememberMeListener;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class SuppressRememberMeListenerTest extends TestCase
{
    /**
     * @var SuppressRememberMeListener
     */
    private $suppressRememberMeListener;

    protected function setUp(): void
    {
        $this->suppressRememberMeListener = new SuppressRememberMeListener();
    }

    /**
     * @return MockObject|RememberMeBadge
     */
    private function createRememberMeBadge(bool $isEnabled): MockObject
    {
        $badge = $this->createMock(RememberMeBadge::class);
        $badge
            ->expects($this->any())
            ->method('isEnabled')
            ->with()
            ->willReturn($isEnabled);

        return $badge;
    }

    /**
     * @return MockObject|Passport
     */
    private function createPassportWithRememberMeBadge(?RememberMeBadge $badge): MockObject
    {
        $passport = $this->createMock(Passport::class);
        $passport
            ->expects($this->any())
            ->method('hasBadge')
            ->with(RememberMeBadge::class)
            ->willReturn(null !== $badge);
        $passport
            ->expects($this->any())
            ->method('getBadge')
            ->with(RememberMeBadge::class)
            ->willReturn($badge);

        return $passport;
    }

    /**
     * @return MockObject|TwoFactorTokenInterface
     */
    private function createTwoFactorToken(): MockObject
    {
        return $this->createMock(TwoFactorTokenInterface::class);
    }

    /**
     * @return MockObject|LoginSuccessEvent
     */
    private function createLoginSuccessEvent(MockObject $passport, TokenInterface $token): MockObject
    {
        $event = $this->createMock(LoginSuccessEvent::class);
        $event
            ->expects($this->any())
            ->method('getPassport')
            ->willReturn($passport);
        $event
            ->expects($this->any())
            ->method('getAuthenticatedToken')
            ->willReturn($token);

        return $event;
    }

    /**
     * @test
     */
    public function onSuccessfulLogin_noRememberMeBadge_doNothing(): void
    {
        $passport = $this->createPassportWithRememberMeBadge(null);
        $token = $this->createTwoFactorToken();
        $event = $this->createLoginSuccessEvent($passport, $token);

        $token
            ->expects($this->never())
            ->method('setAttribute');

        $this->suppressRememberMeListener->onSuccessfulLogin($event);
    }

    /**
     * @test
     */
    public function onSuccessfulLogin_rememberMeBadgeDisabled_doNothing(): void
    {
        $badge = $this->createRememberMeBadge(false);
        $passport = $this->createPassportWithRememberMeBadge($badge);
        $token = $this->createTwoFactorToken();
        $event = $this->createLoginSuccessEvent($passport, $token);

        $badge
            ->expects($this->never())
            ->method('disable');
        $token
            ->expects($this->never())
            ->method('setAttribute');

        $this->suppressRememberMeListener->onSuccessfulLogin($event);
    }

    /**
     * @test
     */
    public function onSuccessfulLogin_noTwoFactorToken_doNothing(): void
    {
        $badge = $this->createRememberMeBadge(true);
        $passport = $this->createPassportWithRememberMeBadge($badge);
        $token = $this->createMock(TokenInterface::class);
        $event = $this->createLoginSuccessEvent($passport, $token);

        $badge
            ->expects($this->never())
            ->method('disable');
        $token
            ->expects($this->never())
            ->method('setAttribute');

        $this->suppressRememberMeListener->onSuccessfulLogin($event);
    }

    /**
     * @test
     */
    public function onSuccessfulLogin_requirementsFulfilled_disableRememberMeAndSetTokenAttribute(): void
    {
        $badge = $this->createRememberMeBadge(true);
        $passport = $this->createPassportWithRememberMeBadge($badge);
        $token = $this->createTwoFactorToken();
        $event = $this->createLoginSuccessEvent($passport, $token);

        $badge
            ->expects($this->once())
            ->method('disable');
        $token
            ->expects($this->once())
            ->method('setAttribute')
            ->with(TwoFactorTokenInterface::ATTRIBUTE_NAME_USE_REMEMBER_ME, true);

        $this->suppressRememberMeListener->onSuccessfulLogin($event);
    }
}
