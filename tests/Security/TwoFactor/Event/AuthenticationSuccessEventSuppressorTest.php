<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Event;

use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\AuthenticationSuccessEventSuppressor;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Event\AuthenticationEvent;

class AuthenticationSuccessEventSuppressorTest extends TestCase
{
    /**
     * @var AuthenticationSuccessEventSuppressor
     */
    private $suppressor;

    protected function setUp(): void
    {
        $this->suppressor = new AuthenticationSuccessEventSuppressor();
    }

    private function createAuthenticationEvent(TokenInterface $token): AuthenticationEvent
    {
        return new AuthenticationEvent($token);
    }

    /**
     * @test
     */
    public function onLogin_twoFactorToken_stopEventPropagation(): void
    {
        $token = $this->createMock(TwoFactorTokenInterface::class);
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
}
