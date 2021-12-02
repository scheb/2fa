<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Handler;

use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Condition\TwoFactorConditionInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Handler\AuthenticationHandlerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Handler\ConditionAuthenticationHandler;

class ConditionAuthenticationHandlerTest extends AbstractAuthenticationHandlerTestCase
{
    private MockObject|AuthenticationHandlerInterface $innerAuthenticationHandler;
    private MockObject|TwoFactorConditionInterface $condition;
    private ConditionAuthenticationHandler $conditionAuthenticationHandler;

    protected function setUp(): void
    {
        $this->innerAuthenticationHandler = $this->getAuthenticationHandlerMock();
        $this->condition = $this->createMock(TwoFactorConditionInterface::class);
        $this->conditionAuthenticationHandler = new ConditionAuthenticationHandler($this->innerAuthenticationHandler, $this->condition);
    }

    private function stubCondition(AuthenticationContextInterface $context, bool $result): void
    {
        $this->condition
            ->expects($this->once())
            ->method('shouldPerformTwoFactorAuthentication')
            ->with($context)
            ->willReturn($result);
    }

    /**
     * @test
     */
    public function beginTwoFactorAuthentication_conditionNotFulfilled_returnSameToken(): void
    {
        $originalToken = $this->createToken();
        $authenticationContext = $this->createAuthenticationContext(null, $originalToken);
        $this->stubCondition($authenticationContext, false);

        $this->innerAuthenticationHandler
            ->expects($this->never())
            ->method($this->anything());

        $returnValue = $this->conditionAuthenticationHandler->beginTwoFactorAuthentication($authenticationContext);
        $this->assertSame($originalToken, $returnValue);
    }

    /**
     * @test
     */
    public function beginTwoFactorAuthentication_ipNotWhitelisted_returnTokenFromInnerAuthenticationHandler(): void
    {
        $transformedToken = $this->createToken();
        $authenticationContext = $this->createAuthenticationContext();
        $this->stubCondition($authenticationContext, true);

        $this->innerAuthenticationHandler
            ->expects($this->once())
            ->method('beginTwoFactorAuthentication')
            ->with($authenticationContext)
            ->willReturn($transformedToken);

        $returnValue = $this->conditionAuthenticationHandler->beginTwoFactorAuthentication($authenticationContext);
        $this->assertSame($transformedToken, $returnValue);
    }
}
