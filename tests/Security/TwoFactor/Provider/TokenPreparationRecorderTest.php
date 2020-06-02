<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Provider;

use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TokenPreparationRecorder;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class TokenPreparationRecorderTest extends TestCase
{
    private const FIREWALL_NAME = 'firewallName';
    private const PROVIDER_NAME = 'providerName';

    /**
     * @var MockObject|TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var TokenPreparationRecorder
     */
    private $recorder;

    protected function setUp(): void
    {
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->recorder = new TokenPreparationRecorder($this->tokenStorage);
    }

    private function stubTokenStorageHasToken(TokenInterface $token): void
    {
        $this->tokenStorage
            ->expects($this->any())
            ->method('getToken')
            ->willReturn($token);
    }

    /**
     * @return MockObject|TwoFactorTokenInterface
     */
    private function createTwoFactorTokenWithFirewallName(): MockObject
    {
        $token = $this->createMock(TwoFactorTokenInterface::class);
        $token
            ->expects($this->any())
            ->method('getProviderKey')
            ->willReturn(self::FIREWALL_NAME);

        return $token;
    }

    /**
     * @test
     */
    public function isTwoFactorProviderPrepared_invalidToken_throwRuntimeException(): void
    {
        $this->stubTokenStorageHasToken($this->createMock(TokenInterface::class));

        $this->expectException(\RuntimeException::class);
        $this->recorder->isTwoFactorProviderPrepared(self::FIREWALL_NAME, self::PROVIDER_NAME);
    }

    /**
     * @test
     */
    public function isTwoFactorProviderPrepared_differentFirewallName_throwLogicException(): void
    {
        $this->stubTokenStorageHasToken($this->createMock(TwoFactorTokenInterface::class));

        $this->expectException(\LogicException::class);
        $this->recorder->isTwoFactorProviderPrepared('differentFirewallName', self::PROVIDER_NAME);
    }

    /**
     * @test
     * @dataProvider provideReturnValues
     */
    public function isTwoFactorProviderPrepared_validToken_setOnToken(bool $expectedReturnValue): void
    {
        $token = $this->createTwoFactorTokenWithFirewallName();
        $this->stubTokenStorageHasToken($token);

        $token
            ->expects($this->once())
            ->method('isTwoFactorProviderPrepared')
            ->with(self::PROVIDER_NAME)
            ->willReturn($expectedReturnValue);

        $returnValue = $this->recorder->isTwoFactorProviderPrepared(self::FIREWALL_NAME, self::PROVIDER_NAME);

        $this->assertEquals($expectedReturnValue, $returnValue);
    }

    public function provideReturnValues(): array
    {
        return [
            [true],
            [false],
        ];
    }

    /**
     * @test
     */
    public function setTwoFactorProviderPrepared_invalidToken_throwRuntimeException(): void
    {
        $this->stubTokenStorageHasToken($this->createMock(TokenInterface::class));

        $this->expectException(\RuntimeException::class);
        $this->recorder->setTwoFactorProviderPrepared(self::FIREWALL_NAME, self::PROVIDER_NAME);
    }

    /**
     * @test
     */
    public function setTwoFactorProviderPrepared_differentFirewallName_throwLogicException(): void
    {
        $this->stubTokenStorageHasToken($this->createMock(TwoFactorTokenInterface::class));

        $this->expectException(\LogicException::class);
        $this->recorder->setTwoFactorProviderPrepared('differentFirewallName', self::PROVIDER_NAME);
    }

    /**
     * @test
     */
    public function setTwoFactorProviderPrepared_validToken_getFromToken(): void
    {
        $token = $this->createTwoFactorTokenWithFirewallName();
        $this->stubTokenStorageHasToken($token);

        $token
            ->expects($this->once())
            ->method('setTwoFactorProviderPrepared')
            ->with(self::PROVIDER_NAME);

        $this->recorder->setTwoFactorProviderPrepared(self::FIREWALL_NAME, self::PROVIDER_NAME);
    }
}
