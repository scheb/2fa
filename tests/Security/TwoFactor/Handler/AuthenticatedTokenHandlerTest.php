<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Handler;

use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\TwoFactor\Handler\AuthenticatedTokenHandler;
use Scheb\TwoFactorBundle\Security\TwoFactor\Handler\AuthenticationHandlerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class AuthenticatedTokenHandlerTest extends AbstractAuthenticationHandlerTestCase
{
    /**
     * @var MockObject|AuthenticationHandlerInterface
     */
    private $innerAuthenticationHandler;

    /**
     * @var AuthenticatedTokenHandler
     */
    private $authenticatedTokenHandler;

    protected function setUp(): void
    {
        $this->innerAuthenticationHandler = $this->getAuthenticationHandlerMock();
        $this->authenticatedTokenHandler = new AuthenticatedTokenHandler($this->innerAuthenticationHandler, [UsernamePasswordToken::class]);
    }

    private function createSupportedSecurityToken(): UsernamePasswordToken
    {
        return new UsernamePasswordToken('user', [], 'firewallName');
    }

    /**
     * @test
     */
    public function beginTwoFactorAuthentication_tokenIsEnabled_returnTokenFromInnerAuthenticationHandler(): void
    {
        $supportedToken = $this->createSupportedSecurityToken();
        $authenticationContext = $this->createAuthenticationContext(null, $supportedToken);
        $transformedToken = $this->createMock(TokenInterface::class);

        $this->innerAuthenticationHandler
            ->expects($this->once())
            ->method('beginTwoFactorAuthentication')
            ->with($authenticationContext)
            ->willReturn($transformedToken);

        $returnValue = $this->authenticatedTokenHandler->beginTwoFactorAuthentication($authenticationContext);
        $this->assertSame($transformedToken, $returnValue);
    }

    /**
     * @test
     */
    public function beginTwoFactorAuthentication_tokenIsNotEnabled_returnSameToken(): void
    {
        $unsupportedToken = $this->createMock(TokenInterface::class);
        $authenticationContext = $this->createAuthenticationContext(null, $unsupportedToken);

        $this->innerAuthenticationHandler
            ->expects($this->never())
            ->method($this->anything());

        $returnValue = $this->authenticatedTokenHandler->beginTwoFactorAuthentication($authenticationContext);
        $this->assertSame($unsupportedToken, $returnValue);
    }
}
