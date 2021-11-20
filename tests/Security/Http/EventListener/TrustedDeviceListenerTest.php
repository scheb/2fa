<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\Http\EventListener;

use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\Http\Authenticator\Passport\Badge\TrustedDeviceBadge;
use Scheb\TwoFactorBundle\Security\Http\Authenticator\Passport\Credentials\TwoFactorCodeCredentials;
use Scheb\TwoFactorBundle\Security\Http\EventListener\TrustedDeviceListener;
use Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\TrustedDeviceManagerInterface;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class TrustedDeviceListenerTest extends TestCase
{
    private const FIREWALL_NAME = 'firewallName';

    /**
     * @var string[]
     */
    private $availableBadges = [];

    /**
     * @var MockObject|LoginSuccessEvent
     */
    private $loginSuccessEvent;

    /**
     * @var MockObject|Request
     */
    private $request;

    /**
     * @var MockObject|TrustedDeviceManagerInterface
     */
    private $trustedDeviceManager;

    /**
     * @var TrustedDeviceListener
     */
    private $trustedDeviceListener;

    protected function setUp(): void
    {
        $this->request = $this->createMock(Request::class);
        $this->loginSuccessEvent = $this->createMock(LoginSuccessEvent::class);
        $this->loginSuccessEvent
            ->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->request);
        $this->loginSuccessEvent
            ->expects($this->any())
            ->method('getFirewallName')
            ->willReturn(self::FIREWALL_NAME);

        $this->trustedDeviceManager = $this->createMock(TrustedDeviceManagerInterface::class);
        $this->trustedDeviceListener = new TrustedDeviceListener($this->trustedDeviceManager);
    }

    /**
     * @return MockObject|Passport
     */
    private function createPassportMock(): MockObject
    {
        $passport = $this->createMock(Passport::class);
        $passport
            ->expects($this->any())
            ->method('hasBadge')
            ->willReturnCallback(function (string $badgeClass) {
                return \in_array($badgeClass, $this->availableBadges);
            });

        return $passport;
    }

    private function stubPassportHasBadge(string $badgeClass): void
    {
        $this->availableBadges[] = $badgeClass;
    }

    private function stubAuthenticatedToken(TokenInterface $authenticatedToken): void
    {
        $this->loginSuccessEvent
            ->expects($this->any())
            ->method('getAuthenticatedToken')
            ->willReturn($authenticatedToken);
    }

    private function stubPassport(Passport $passport): void
    {
        $this->loginSuccessEvent
            ->expects($this->any())
            ->method('getPassport')
            ->willReturn($passport);
    }

    /**
     * @test
     */
    public function onSuccessfulLogin_noTwoFactorPassportCredentials_doNothing(): void
    {
        $passport = $this->createPassportMock();
        $authenticatedToken = $this->createMock(TokenInterface::class);

        $this->stubAuthenticatedToken($authenticatedToken);
        $this->stubPassport($passport);
        $this->stubPassportHasBadge(TrustedDeviceBadge::class);

        $this->trustedDeviceManager
            ->expects($this->never())
            ->method($this->anything());

        $this->trustedDeviceListener->onSuccessfulLogin($this->loginSuccessEvent);
    }

    /**
     * @test
     */
    public function onSuccessfulLogin_hasTwoFactorCredentials_doNothing(): void
    {
        $passport = $this->createMock(Passport::class);
        $authenticatedToken = $this->createMock(TwoFactorTokenInterface::class);

        $this->stubAuthenticatedToken($authenticatedToken);
        $this->stubPassport($passport);
        $this->stubPassportHasBadge(TwoFactorCodeCredentials::class);
        $this->stubPassportHasBadge(TrustedDeviceBadge::class);

        $this->trustedDeviceManager
            ->expects($this->never())
            ->method($this->anything());

        $this->trustedDeviceListener->onSuccessfulLogin($this->loginSuccessEvent);
    }

    /**
     * @test
     */
    public function onSuccessfulLogin_noTrustedDeviceBadge_doNothing(): void
    {
        $passport = $this->createPassportMock();
        $authenticatedToken = $this->createMock(TokenInterface::class);

        $this->stubAuthenticatedToken($authenticatedToken);
        $this->stubPassport($passport);
        $this->stubPassportHasBadge(TwoFactorCodeCredentials::class);

        $this->trustedDeviceManager
            ->expects($this->never())
            ->method($this->anything());

        $this->trustedDeviceListener->onSuccessfulLogin($this->loginSuccessEvent);
    }

    /**
     * @test
     */
    public function onSuccessfulLogin_cannotSetTrustedDevice_notSetTrustedDevice(): void
    {
        $passport = $this->createPassportMock();
        $authenticatedToken = $this->createMock(TokenInterface::class);
        $user = $this->createMock(UserInterface::class);

        $this->stubAuthenticatedToken($authenticatedToken);
        $this->stubPassport($passport);
        $this->stubPassportHasBadge(TwoFactorCodeCredentials::class);
        $this->stubPassportHasBadge(TrustedDeviceBadge::class);

        $authenticatedToken
            ->expects($this->any())
            ->method('getUser')
            ->willReturn($user);

        $this->trustedDeviceManager
            ->expects($this->once())
            ->method('canSetTrustedDevice')
            ->with($user, $this->request, self::FIREWALL_NAME)
            ->willReturn(false);

        $this->trustedDeviceManager
            ->expects($this->never())
            ->method('addTrustedDevice');

        $this->trustedDeviceListener->onSuccessfulLogin($this->loginSuccessEvent);
    }

    /**
     * @test
     */
    public function onSuccessfulLogin_canSetTrustedDevice_setTrustedDevice(): void
    {
        $passport = $this->createPassportMock();
        $authenticatedToken = $this->createMock(TokenInterface::class);
        $user = $this->createMock(UserInterface::class);

        $this->stubAuthenticatedToken($authenticatedToken);
        $this->stubPassport($passport);
        $this->stubPassportHasBadge(TwoFactorCodeCredentials::class);
        $this->stubPassportHasBadge(TrustedDeviceBadge::class);

        $authenticatedToken
            ->expects($this->any())
            ->method('getUser')
            ->willReturn($user);

        $this->trustedDeviceManager
            ->expects($this->any())
            ->method('canSetTrustedDevice')
            ->with($user, $this->request, self::FIREWALL_NAME)
            ->willReturn(true);

        $this->trustedDeviceManager
            ->expects($this->once())
            ->method('addTrustedDevice')
            ->with($user, self::FIREWALL_NAME);

        $this->trustedDeviceListener->onSuccessfulLogin($this->loginSuccessEvent);
    }
}
