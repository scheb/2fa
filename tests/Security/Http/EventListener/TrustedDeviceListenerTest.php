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
use function in_array;

class TrustedDeviceListenerTest extends TestCase
{
    private const FIREWALL_NAME = 'firewallName';

    private MockObject|LoginSuccessEvent $loginSuccessEvent;
    private MockObject|Request $request;
    private MockObject|UserInterface $user;
    private MockObject|TrustedDeviceManagerInterface $trustedDeviceManager;
    private TrustedDeviceListener $trustedDeviceListener;

    /** @var string[] */
    private array $availableBadges = [];

    protected function setUp(): void
    {
        $this->request = $this->createMock(Request::class);
        $this->user = $this->createMock(UserInterface::class);
        $this->loginSuccessEvent = $this->createMock(LoginSuccessEvent::class);
        $this->loginSuccessEvent
            ->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->request);
        $this->loginSuccessEvent
            ->expects($this->any())
            ->method('getFirewallName')
            ->willReturn(self::FIREWALL_NAME);
        $this->loginSuccessEvent
            ->expects($this->any())
            ->method('getUser')
            ->willReturn($this->user);

        $this->trustedDeviceManager = $this->createMock(TrustedDeviceManagerInterface::class);
        $this->trustedDeviceListener = new TrustedDeviceListener($this->trustedDeviceManager);
    }

    private function createPassportMock(): MockObject|Passport
    {
        $passport = $this->createMock(Passport::class);
        $passport
            ->expects($this->any())
            ->method('hasBadge')
            ->willReturnCallback(function (string $badgeClass) {
                return in_array($badgeClass, $this->availableBadges);
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

        $this->stubAuthenticatedToken($authenticatedToken);
        $this->stubPassport($passport);
        $this->stubPassportHasBadge(TwoFactorCodeCredentials::class);
        $this->stubPassportHasBadge(TrustedDeviceBadge::class);

        $this->trustedDeviceManager
            ->expects($this->once())
            ->method('canSetTrustedDevice')
            ->with($this->user, $this->request, self::FIREWALL_NAME)
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

        $this->stubAuthenticatedToken($authenticatedToken);
        $this->stubPassport($passport);
        $this->stubPassportHasBadge(TwoFactorCodeCredentials::class);
        $this->stubPassportHasBadge(TrustedDeviceBadge::class);

        $this->trustedDeviceManager
            ->expects($this->any())
            ->method('canSetTrustedDevice')
            ->with($this->user, $this->request, self::FIREWALL_NAME)
            ->willReturn(true);

        $this->trustedDeviceManager
            ->expects($this->once())
            ->method('addTrustedDevice')
            ->with($this->user, self::FIREWALL_NAME);

        $this->trustedDeviceListener->onSuccessfulLogin($this->loginSuccessEvent);
    }
}
