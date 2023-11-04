<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Condition;

use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

abstract class AbstractAuthenticationContextTestCase extends TestCase
{
    protected const FIREWALL_NAME = 'firewallName';

    protected function createAuthenticationContext(Request|null $request = null, TokenInterface|null $token = null, UserInterface|null $user = null): MockObject|AuthenticationContextInterface
    {
        $context = $this->createMock(AuthenticationContextInterface::class);
        $context
            ->expects($this->any())
            ->method('getRequest')
            ->willReturn($request ?: $this->createRequest());

        $context
            ->expects($this->any())
            ->method('getToken')
            ->willReturn($token ?: $this->createToken());

        $context
            ->expects($this->any())
            ->method('getUser')
            ->willReturn($user ?: $this->createUser());

        $context
            ->expects($this->any())
            ->method('getFirewallName')
            ->willReturn(self::FIREWALL_NAME);

        return $context;
    }

    protected function createRequest(): MockObject|Request
    {
        return $this->createMock(Request::class);
    }

    protected function createToken(): MockObject|TokenInterface
    {
        return $this->createMock(TokenInterface::class);
    }

    protected function createUser(): MockObject|UserInterface
    {
        return $this->createMock(UserInterface::class);
    }

    protected function createResponse(): Response
    {
        $response = new Response();
        $response->headers = new ResponseHeaderBag();

        return $response;
    }
}
