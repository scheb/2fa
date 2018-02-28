<?php

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Trusted;

use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\TrustedDeviceManager;
use Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\TrustedDeviceTokenStorage;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;

class TrustedDeviceManagerTest extends TestCase
{
    /**
     * @var MockObject|TrustedDeviceTokenStorage
     */
    private $trustedTokenStorage;

    /**
     * @var TrustedDeviceManager
     */
    private $trustedDeviceManager;

    protected function setUp()
    {
        $this->trustedTokenStorage = $this->createMock(TrustedDeviceTokenStorage::class);
    }

    private function createTrustedDeviceManager(bool $useTrustedOption): void
    {
        $this->trustedDeviceManager = new TrustedDeviceManager($this->trustedTokenStorage, $useTrustedOption);
    }

    private function stubUsername(MockObject $userMock, string $username)
    {
        $userMock
            ->expects($this->any())
            ->method('getUsername')
            ->willReturn($username);
    }

    private function stubTrustedTokenVersion(MockObject $user, int $version)
    {
        $user
            ->expects($this->any())
            ->method('getTrustedTokenVersion')
            ->willReturn($version);
    }

    /**
     * @test
     */
    public function addTrustedDevice_trustedOptionDisabled_doNothing()
    {
        $this->createTrustedDeviceManager(false);
        $user = $this->createMock(UserInterfaceWithTrustedDeviceInterface::class);

        $this->trustedTokenStorage
            ->expects($this->never())
            ->method($this->anything());

        $this->trustedDeviceManager->addTrustedDevice($user, 'firewallName');
    }

    /**
     * @test
     */
    public function addTrustedDevice_notUserInterface_doNothing()
    {
        $this->createTrustedDeviceManager(true);
        $this->trustedTokenStorage
            ->expects($this->never())
            ->method($this->anything());

        $user = new \stdClass();
        $this->trustedDeviceManager->addTrustedDevice($user, 'firewallName');
    }

    /**
     * @test
     */
    public function addTrustedDevice_supportsTrustedDeviceInterface_addTrustedTokenWithVersion()
    {
        $this->createTrustedDeviceManager(true);
        $user = $this->createMock(UserInterfaceWithTrustedDeviceInterface::class);
        $this->stubUsername($user, 'username');
        $this->stubTrustedTokenVersion($user, 123);

        $this->trustedTokenStorage
            ->expects($this->once())
            ->method('addTrustedToken')
            ->with('username', 'firewallName', 123);

        $this->trustedDeviceManager->addTrustedDevice($user, 'firewallName');
    }

    /**
     * @test
     */
    public function addTrustedDevice_notSupportsTrustedDeviceInterface_addTrustedTokenWithDefaultVersion()
    {
        $this->createTrustedDeviceManager(true);
        $user = $this->createMock(UserInterface::class);
        $this->stubUsername($user, 'username');

        $this->trustedTokenStorage
            ->expects($this->once())
            ->method('addTrustedToken')
            ->with('username', 'firewallName', 0);

        $this->trustedDeviceManager->addTrustedDevice($user, 'firewallName');
    }

    /**
     * @test
     */
    public function isTrustedDevice_trustedOptionDisabled_alwaysReturnFalse()
    {
        $this->createTrustedDeviceManager(false);
        $user = $this->createMock(UserInterfaceWithTrustedDeviceInterface::class);

        $this->trustedTokenStorage
            ->expects($this->never())
            ->method($this->anything());

        $returnValue = $this->trustedDeviceManager->isTrustedDevice($user, 'firewallName');
        $this->assertFalse($returnValue);
    }

    /**
     * @test
     */
    public function isTrustedDevice_notUserInterface_doNothing()
    {
        $this->createTrustedDeviceManager(true);
        $this->trustedTokenStorage
            ->expects($this->never())
            ->method($this->anything());

        $user = new \stdClass();
        $this->trustedDeviceManager->isTrustedDevice($user, 'firewallName');
    }

    /**
     * @test
     */
    public function isTrustedDevice_supportsTrustedDeviceInterface_checkHasTrustedTokenWithVersion()
    {
        $this->createTrustedDeviceManager(true);
        $user = $this->createMock(UserInterfaceWithTrustedDeviceInterface::class);
        $this->stubUsername($user, 'username');
        $this->stubTrustedTokenVersion($user, 123);

        $this->trustedTokenStorage
            ->expects($this->once())
            ->method('hasTrustedToken')
            ->with('username', 'firewallName', 123);

        $this->trustedDeviceManager->isTrustedDevice($user, 'firewallName');
    }

    /**
     * @test
     */
    public function addTrustedDevice_notSupportsTrustedDeviceInterface_checkHasTrustedTokenWithDefaultVersion()
    {
        $this->createTrustedDeviceManager(true);
        $user = $this->createMock(UserInterface::class);
        $this->stubUsername($user, 'username');

        $this->trustedTokenStorage
            ->expects($this->once())
            ->method('hasTrustedToken')
            ->with('username', 'firewallName', 0);

        $this->trustedDeviceManager->isTrustedDevice($user, 'firewallName');
    }

    /**
     * @test
     * @dataProvider provideIsTrustedDeviceReturnValues
     */
    public function addTrustedDevice_notSupportsTrustedDeviceInterface_returnResult(bool $result)
    {
        $this->createTrustedDeviceManager(true);
        $user = $this->createMock(UserInterface::class);
        $this->stubUsername($user, 'username');

        $this->trustedTokenStorage
            ->expects($this->once())
            ->method('hasTrustedToken')
            ->willReturn($result);

        $returnValue = $this->trustedDeviceManager->isTrustedDevice($user, 'firewallName');
        $this->assertEquals($result, $returnValue);
    }


    public function provideIsTrustedDeviceReturnValues(): array
    {
        return [
            [true],
            [false],
        ];
    }
}
