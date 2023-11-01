<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Trusted;

use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\TrustedDeviceManager;
use Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\TrustedDeviceTokenStorage;
use Scheb\TwoFactorBundle\Tests\TestCase;
use stdClass;
use Symfony\Component\Security\Core\User\UserInterface;
use function method_exists;

class TrustedDeviceManagerTest extends TestCase
{
    private MockObject|TrustedDeviceTokenStorage $trustedTokenStorage;
    private TrustedDeviceManager $trustedDeviceManager;

    protected function setUp(): void
    {
        $this->trustedTokenStorage = $this->createMock(TrustedDeviceTokenStorage::class);
        $this->trustedDeviceManager = new TrustedDeviceManager($this->trustedTokenStorage);
    }

    private function stubUsername(MockObject $userMock, string $username): void
    {
        if (method_exists(UserInterface::class, 'getUserIdentifier')) {
            $userMock
                ->expects($this->any())
                ->method('getUserIdentifier')
                ->willReturn($username);
        } else {
            $userMock
                ->expects($this->any())
                ->method('getUsername')
                ->willReturn($username);
        }
    }

    private function stubTrustedTokenVersion(MockObject $user, int $version): void
    {
        $user
            ->expects($this->any())
            ->method('getTrustedTokenVersion')
            ->willReturn($version);
    }

    /**
     * @test
     */
    public function addTrustedDevice_notUserInterface_doNothing(): void
    {
        $this->trustedTokenStorage
            ->expects($this->never())
            ->method($this->anything());

        $user = new stdClass();
        $this->trustedDeviceManager->addTrustedDevice($user, 'firewallName');
    }

    /**
     * @test
     */
    public function addTrustedDevice_supportsTrustedDeviceInterface_addTrustedTokenWithVersion(): void
    {
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
    public function addTrustedDevice_notSupportsTrustedDeviceInterface_addTrustedTokenWithDefaultVersion(): void
    {
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
    public function isTrustedDevice_notUserInterface_doNothing(): void
    {
        $this->trustedTokenStorage
            ->expects($this->never())
            ->method($this->anything());

        $user = new stdClass();
        $this->trustedDeviceManager->isTrustedDevice($user, 'firewallName');
    }

    /**
     * @test
     */
    public function isTrustedDevice_supportsTrustedDeviceInterface_checkHasTrustedTokenWithVersion(): void
    {
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
    public function addTrustedDevice_notSupportsTrustedDeviceInterface_checkHasTrustedTokenWithDefaultVersion(): void
    {
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
    public function addTrustedDevice_notSupportsTrustedDeviceInterface_returnResult(bool $result): void
    {
        $user = $this->createMock(UserInterface::class);
        $this->stubUsername($user, 'username');

        $this->trustedTokenStorage
            ->expects($this->once())
            ->method('hasTrustedToken')
            ->willReturn($result);

        $returnValue = $this->trustedDeviceManager->isTrustedDevice($user, 'firewallName');
        $this->assertEquals($result, $returnValue);
    }

    /**
     * @return array<array<bool>>
     */
    public static function provideIsTrustedDeviceReturnValues(): array
    {
        return [
            [true],
            [false],
        ];
    }
}
