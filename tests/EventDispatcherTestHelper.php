<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests;

use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use function count;

trait EventDispatcherTestHelper
{
    protected MockObject|EventDispatcherInterface $eventDispatcher;

    protected function expectDispatchOneEvent(mixed $eventObjectAssertion, string $eventNameAssertion): void
    {
        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($eventObjectAssertion, $eventNameAssertion);
    }

    protected function expectNotDispatchEvent(): void
    {
        $this->eventDispatcher
            ->expects($this->never())
            ->method($this->anything());
    }

    /**
     * @param array<int, array<mixed>> $events
     */
    protected function expectDispatchConsecutiveEvents(array $events): void
    {
        $matcher = $this->exactly(count($events));
        $this->eventDispatcher
            ->expects($matcher)
            ->method('dispatch')
            ->with(
                $this->callback(function ($value) use ($matcher, $events) {
                    $assertValue = $events[$matcher->numberOfInvocations() - 1][0];

                    // When a PHPUnit constraint is passed
                    if ($assertValue instanceof Constraint) {
                        $assertValue->evaluate($value);
                    } else {
                        $this->assertEquals($assertValue, $value);
                    }

                    return true;
                }),
                $this->callback(function ($value) use ($matcher, $events) {
                    $assertValue = $events[$matcher->numberOfInvocations() - 1][1];

                    // When a PHPUnit constraint is passed
                    if ($assertValue instanceof Constraint) {
                        $assertValue->evaluate($value);
                    } else {
                        $this->assertEquals($assertValue, $value);
                    }

                    return true;
                }),
            );
    }
}
