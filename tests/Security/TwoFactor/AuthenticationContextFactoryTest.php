<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor;

use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContext;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextFactory;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

class AuthenticationContextFactoryTest extends TestCase
{
    private MockObject|Request $request;
    private MockObject|TokenInterface $token;
    private MockObject|Passport $passport;
    private AuthenticationContextFactory $authenticationContextFactory;

    protected function setUp(): void
    {
        $this->request = $this->createMock(Request::class);
        $this->token = $this->createMock(TokenInterface::class);
        $this->passport = $this->createMock(Passport::class);
        $this->authenticationContextFactory = new AuthenticationContextFactory(AuthenticationContext::class);
    }

    /**
     * @test
     */
    public function create_onCreate_returnAuthenticationContext(): void
    {
        $this->assertInstanceOf(
            AuthenticationContext::class,
            $this->authenticationContextFactory->create($this->request, $this->token, $this->passport, 'firewallName')
        );
    }
}
