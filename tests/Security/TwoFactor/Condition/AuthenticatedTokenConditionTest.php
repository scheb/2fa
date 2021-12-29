<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Condition;

use Scheb\TwoFactorBundle\Security\TwoFactor\Condition\AuthenticatedTokenCondition;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserInterface;

class AuthenticatedTokenConditionTest extends AbstractAuthenticationContextTestCase
{
    private AuthenticatedTokenCondition $authenticatedTokenHandler;

    protected function setUp(): void
    {
        $this->authenticatedTokenHandler = new AuthenticatedTokenCondition([UsernamePasswordToken::class]);
    }

    private function createSupportedSecurityToken(): UsernamePasswordToken
    {
        return new UsernamePasswordToken($this->createMock(UserInterface::class), 'firewallName');
    }

    /**
     * @test
     */
    public function shouldPerformTwoFactorAuthentication_tokenIsEnabled_returnTrue(): void
    {
        $supportedToken = $this->createSupportedSecurityToken();
        $authenticationContext = $this->createAuthenticationContext(null, $supportedToken);
        $transformedToken = $this->createMock(TokenInterface::class);

        $returnValue = $this->authenticatedTokenHandler->shouldPerformTwoFactorAuthentication($authenticationContext);
        $this->assertTrue($returnValue);
    }

    /**
     * @test
     */
    public function shouldPerformTwoFactorAuthentication_tokenIsNotEnabled_returnFalse(): void
    {
        $unsupportedToken = $this->createMock(TokenInterface::class);
        $authenticationContext = $this->createAuthenticationContext(null, $unsupportedToken);

        $returnValue = $this->authenticatedTokenHandler->shouldPerformTwoFactorAuthentication($authenticationContext);
        $this->assertFalse($returnValue);
    }
}
