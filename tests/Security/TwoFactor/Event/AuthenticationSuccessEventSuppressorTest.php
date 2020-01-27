<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Event;

use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\AuthenticationSuccessEventSuppressor;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Event\AuthenticationEvent;

class AuthenticationSuccessEventSuppressorTest extends TestCase
{
    private const FIREWALL_NAME = 'firewallName';

    /**
     * @var AuthenticationSuccessEventSuppressor
     */
    private $suppressor;

    protected function setUp(): void
    {
        $this->suppressor = new AuthenticationSuccessEventSuppressor(self::FIREWALL_NAME);
    }

    private function createTwoFactorToken(string $firewallName): MockObject
    {
        $token = $this->createMock(TwoFactorTokenInterface::class);
        $token
            ->expects($this->any())
            ->method('getProviderKey')
            ->willReturn($firewallName);

        return $token;
    }

    private function createAuthenticationEvent(TokenInterface $token): AuthenticationEvent
    {
        return new AuthenticationEvent($token);
    }

    /**
     * @test
     */
    public function onLogin_twoFactorTokenFirewallMatches_stopEventPropagation(): void
    {
        $token = $this->createTwoFactorToken(self::FIREWALL_NAME);
        $event = $this->createAuthenticationEvent($token);

        $this->suppressor->onLogin($event);

        $this->assertTrue($event->isPropagationStopped());
    }

    /**
     * @test
     */
    public function onLogin_noTwoFactorToken_doNotStopEventPropagation(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $event = $this->createAuthenticationEvent($token);

        $this->suppressor->onLogin($event);

        $this->assertFalse($event->isPropagationStopped());
    }

    /**
     * @test
     */
    public function onLogin_differentFirewallName_doNotStopEventPropagation(): void
    {
        $token = $this->createTwoFactorToken('differentFirewallName');
        $event = $this->createAuthenticationEvent($token);

        $this->suppressor->onLogin($event);

        $this->assertFalse($event->isPropagationStopped());
    }
}
