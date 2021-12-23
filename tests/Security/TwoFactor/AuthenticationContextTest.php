<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor;

use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContext;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

class AuthenticationContextTest extends TestCase
{
    private MockObject|Request $request;
    private MockObject|TokenInterface $token;
    private AuthenticationContext $authContext;
    private MockObject|Passport $passport;

    protected function setUp(): void
    {
        $this->request = $this->createMock(Request::class);
        $this->token = $this->createMock(TokenInterface::class);
        $this->passport = $this->createMock(Passport::class);
        $this->authContext = new AuthenticationContext($this->request, $this->token, $this->passport, 'firewallName');
    }

    /**
     * @test
     */
    public function getToken_objectInitialized_returnToken(): void
    {
        $returnValue = $this->authContext->getToken();
        $this->assertEquals($this->token, $returnValue);
    }

    /**
     * @test
     */
    public function getPassport_objectInitialized_returnPassport(): void
    {
        $returnValue = $this->authContext->getPassport();
        $this->assertEquals($this->passport, $returnValue);
    }

    /**
     * @test
     */
    public function getRequest_objectInitialized_returnRequest(): void
    {
        $returnValue = $this->authContext->getRequest();
        $this->assertEquals($this->request, $returnValue);
    }

    /**
     * @test
     */
    public function getSession_objectInitialized_returnSession(): void
    {
        //Mock the Request object
        $session = $this->createMock(SessionInterface::class);
        $this->request
            ->expects($this->once())
            ->method('getSession')
            ->willReturn($session);

        $returnValue = $this->authContext->getSession();
        $this->assertEquals($session, $returnValue);
    }

    /**
     * @test
     * @dataProvider provideUserObjectAndReturnValue
     */
    public function getUser_objectInitialized_returnValid($userObject, $expectedReturnValue): void
    {
        //Mock the TokenInterface
        $this->token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($userObject);

        $returnValue = $this->authContext->getUser();
        $this->assertEquals($expectedReturnValue, $returnValue);
    }

    public function provideUserObjectAndReturnValue(): array
    {
        $user = $this->createMock(UserInterface::class);

        return [
            [$user, $user],
            [null, null],
        ];
    }

    /**
     * @test
     */
    public function getFirewallName_hasValue_returnFirewallName(): void
    {
        $returnValue = $this->authContext->getFirewallName();
        $this->assertEquals('firewallName', $returnValue);
    }
}
