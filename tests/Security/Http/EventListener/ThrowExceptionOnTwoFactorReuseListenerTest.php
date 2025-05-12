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
    private const MFA_CODE = '123456';

    #[Test]
    public function handle_codeReuseIsTriggered_exceptionIsThrown(): void
    {
        $listener = new ThrowExceptionOnTwoFactorCodeReuseListener();

        try {
            $listener->handle(new TwoFactorCodeReusedEvent(
                $this->createMock(UserInterface::class),
                self::MFA_CODE,
            ));
        } catch (ReusedTwoFactorCodeException $exception) {
            $this->assertSame(0, $exception->getCode());
            $this->assertSame('code_reused', $exception->getMessageKey());
            $this->assertSame('', $exception->getMessage());
        }
    }

    #[Test]
    public function EventIsHandledWithCorrectPriority(): void
    {
        $this->assertSame([
            TwoFactorCodeReusedEvent::class => ['handle', -256],
        ], ThrowExceptionOnTwoFactorCodeReuseListener::getSubscribedEvents());
    }
}
