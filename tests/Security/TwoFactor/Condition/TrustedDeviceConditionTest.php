<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Condition;

use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\TwoFactor\Condition\TrustedDeviceCondition;
use Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\TrustedDeviceManager;

class TrustedDeviceConditionTest extends AbstractAuthenticationContextTestCase
{
    private MockObject|TrustedDeviceManager $trustedDeviceManager;
    private TrustedDeviceCondition $trustedHandler;

    protected function setUp(): void
    {
        $this->trustedDeviceManager = $this->createMock(TrustedDeviceManager::class);
        $this->trustedHandler = $this->createTrustedHandler(false);
    }

    private function createTrustedHandler(bool $extendTrustedToken): TrustedDeviceCondition
    {
        return new TrustedDeviceCondition($this->trustedDeviceManager, $extendTrustedToken);
    }

    private function stubIsTrustedDevice(bool $isTrustedDevice): void
    {
        $this->trustedDeviceManager
            ->expects($this->any())
            ->method('isTrustedDevice')
            ->willReturn($isTrustedDevice);
    }

    private function stubCanSetTrustedDevice(bool $canSetTrustedDevice): void
    {
        $this->trustedDeviceManager
            ->expects($this->any())
            ->method('canSetTrustedDevice')
            ->willReturn($canSetTrustedDevice);
    }

    /**
     * @test
     */
    public function beginAuthentication_trustedOptionEnabled_checkTrustedToken(): void
    {
        $user = $this->createUser();
        $context = $this->createAuthenticationContext(null, null, $user);

        $this->trustedDeviceManager
            ->expects($this->once())
            ->method('isTrustedDevice')
            ->with($user, 'firewallName');

        $this->trustedHandler->shouldPerformTwoFactorAuthentication($context);
    }

    /**
     * @test
     */
    public function beginAuthentication_isTrustedDevice_returnFalse(): void
    {
        $originalToken = $this->createToken();
        $context = $this->createAuthenticationContext(null, $originalToken);
        $this->stubIsTrustedDevice(true);

        $returnValue = $this->trustedHandler->shouldPerformTwoFactorAuthentication($context);
        $this->assertFalse($returnValue);
    }

    /**
     * @test
     */
    public function beginAuthentication_isTrustedDeviceAndExtensionAllowed_addNewTrustedToken(): void
    {
        $trustedHandler = $this->createTrustedHandler(true);
        $user = $this->createUser();
        $context = $this->createAuthenticationContext(null, null, $user);
        $this->stubIsTrustedDevice(true);
        $this->stubCanSetTrustedDevice(true);

        $this->trustedDeviceManager
            ->expects($this->once())
            ->method('addTrustedDevice')
            ->with($user, 'firewallName');

        $trustedHandler->shouldPerformTwoFactorAuthentication($context);
    }

    /**
     * @test
     */
    public function beginAuthentication_isTrustedDeviceAndConfiguredNotExtendTrustedToken_notAddNewTrustedToken(): void
    {
        $trustedHandler = $this->createTrustedHandler(false);
        $user = $this->createUser();
        $context = $this->createAuthenticationContext(null, null, $user);
        $this->stubIsTrustedDevice(true);
        $this->stubCanSetTrustedDevice(true);

        $this->trustedDeviceManager
            ->expects($this->never())
            ->method('addTrustedDevice');

        $trustedHandler->shouldPerformTwoFactorAuthentication($context);
    }

    /**
     * @test
     */
    public function beginAuthentication_isTrustedDeviceAndNotAllowedToSet_notAddNewTrustedToken(): void
    {
        $trustedHandler = $this->createTrustedHandler(true);
        $user = $this->createUser();
        $context = $this->createAuthenticationContext(null, null, $user);
        $this->stubIsTrustedDevice(true);
        $this->stubCanSetTrustedDevice(false);

        $this->trustedDeviceManager
            ->expects($this->never())
            ->method('addTrustedDevice');

        $trustedHandler->shouldPerformTwoFactorAuthentication($context);
    }

    /**
     * @test
     */
    public function beginAuthentication_notTrustedDevice_returnTrue(): void
    {
        $context = $this->createAuthenticationContext();
        $transformedToken = $this->createToken();
        $this->stubIsTrustedDevice(false);

        $this->trustedDeviceManager
            ->expects($this->never())
            ->method('addTrustedDevice');

        $returnValue = $this->trustedHandler->shouldPerformTwoFactorAuthentication($context);
        $this->assertTrue($returnValue);
    }
}
