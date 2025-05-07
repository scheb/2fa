<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\Http\EventListener;

use PHPUnit\Framework\Attributes\Test;
use Scheb\TwoFactorBundle\Security\Authentication\Exception\ReusedTwoFactorCodeException;
use Scheb\TwoFactorBundle\Security\Http\EventListener\CheckTwoFactorCodeListener;
use Scheb\TwoFactorBundle\Security\Http\EventListener\ThrowExceptionOnTwoFactorCodeReuseListener;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorCodeReusedEvent;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @property CheckTwoFactorCodeListener $listener
 */
class ThrowExceptionOnTwoFactorReuseListenerTest extends TestCase
{
    #[Test]
    public function throwExceptionWhenUsed(): void
    {
        $listener = new ThrowExceptionOnTwoFactorCodeReuseListener();

        $this->expectException(ReusedTwoFactorCodeException::class);

        $listener->handle(new TwoFactorCodeReusedEvent(
            $this->createMock(UserInterface::class),
            '123456',
        ));
    }

    #[Test]
    public function EventIsHandledWithCorrectPriority(): void
    {
        $this->assertSame([
            TwoFactorCodeReusedEvent::class => ['handle', -256],
        ], ThrowExceptionOnTwoFactorCodeReuseListener::getSubscribedEvents());
    }
}
