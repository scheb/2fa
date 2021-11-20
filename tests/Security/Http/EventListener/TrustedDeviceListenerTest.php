<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\Http\EventListener;

use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\Http\Authenticator\Passport\Badge\TrustedDeviceBadge;
use Scheb\TwoFactorBundle\Security\Http\Authenticator\Passport\TwoFactorPassport;
use Scheb\TwoFactorBundle\Security\Http\EventListener\TrustedDeviceListener;
use Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\TrustedDeviceManagerInterface;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class TrustedDeviceListenerTest extends TestCase
{
    private const FIREWALL_NAME = 'firewallName';

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

        $this->trustedDeviceManager = $this->createMock(TrustedDeviceManagerInterface::class);
        $this->trustedDeviceListener = new TrustedDeviceListener($this->trustedDeviceManager);
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

    private function stubPassportHasTrustedDeviceBadge(MockObject $passport): void
    {
        $passport
            ->expects($this->any())
            ->method('hasBadge')
            ->with(TrustedDeviceBadge::class)
            ->willReturn(true);
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
        $this->stubPassportHasTrustedDeviceBadge($passport);

        $this->trustedDeviceManager
            ->expects($this->never())
            ->method($this->anything());

        $this->trustedDeviceListener->onSuccessfulLogin($this->loginSuccessEvent);
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
        $this->stubPassportHasTrustedDeviceBadge($passport);

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
        $passport = $this->createMock(TwoFactorPassport::class);
        $authenticatedToken = $this->createMock(TokenInterface::class);

        $this->stubAuthenticatedToken($authenticatedToken);
        $this->stubPassport($passport);

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
        $passport = $this->createMock(TwoFactorPassport::class);
        $authenticatedToken = $this->createMock(TokenInterface::class);
        $user = $this->createMock(UserInterface::class);

        $this->stubAuthenticatedToken($authenticatedToken);
        $this->stubPassport($passport);
        $this->stubPassportHasTrustedDeviceBadge($passport);

        $passport
            ->expects($this->any())
            ->method('getFirewallName')
            ->willReturn(self::FIREWALL_NAME);

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
        $passport = $this->createMock(TwoFactorPassport::class);
        $authenticatedToken = $this->createMock(TokenInterface::class);
        $user = $this->createMock(UserInterface::class);

        $this->stubAuthenticatedToken($authenticatedToken);
        $this->stubPassport($passport);
        $this->stubPassportHasTrustedDeviceBadge($passport);

        $passport
            ->expects($this->any())
            ->method('getFirewallName')
            ->willReturn(self::FIREWALL_NAME);

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
