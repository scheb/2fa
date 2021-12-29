<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Condition;

use ArrayIterator;
use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Condition\TwoFactorConditionInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Condition\TwoFactorConditionRegistry;
use Scheb\TwoFactorBundle\Tests\TestCase;

class TwoFactorConditionRegistryTest extends TestCase
{
    private MockObject|AuthenticationContextInterface $context;
    private MockObject|TwoFactorConditionInterface $condition1;
    private MockObject|TwoFactorConditionInterface $condition2;
    private TwoFactorConditionRegistry $registry;

    protected function setUp(): void
    {
        $this->context = $this->createMock(AuthenticationContextInterface::class);
        $this->condition1 = $this->createMock(TwoFactorConditionInterface::class);
        $this->condition2 = $this->createMock(TwoFactorConditionInterface::class);
        $this->condition3 = $this->createMock(TwoFactorConditionInterface::class);
        $this->registry = new TwoFactorConditionRegistry(new ArrayIterator([
            $this->condition1,
            $this->condition2,
            $this->condition3,
        ]));
    }

    private function conditionReturns(MockObject $condition, bool $result): void
    {
        $condition
            ->expects($this->once())
            ->method('shouldPerformTwoFactorAuthentication')
            ->with($this->context)
            ->willReturn($result);
    }

    private function conditionNotCalled(MockObject $condition): void
    {
        $condition
            ->expects($this->never())
            ->method($this->anything());
    }

    /**
     * @test
     */
    public function shouldPerformTwoFactorAuthentication_allConditionsFulfilled_checkEachConditionsAndReturnTrue(): void
    {
        $this->conditionReturns($this->condition1, true);
        $this->conditionReturns($this->condition2, true);
        $this->conditionReturns($this->condition3, true);

        $returnValue = $this->registry->shouldPerformTwoFactorAuthentication($this->context);
        $this->assertTrue($returnValue);
    }

    /**
     * @test
     */
    public function shouldPerformTwoFactorAuthentication_conditionFails_skipFollowingConditionsAndReturnFalse(): void
    {
        $this->conditionReturns($this->condition1, true);
        $this->conditionReturns($this->condition2, false);
        $this->conditionNotCalled($this->condition3);

        $returnValue = $this->registry->shouldPerformTwoFactorAuthentication($this->context);
        $this->assertFalse($returnValue);
    }
}
