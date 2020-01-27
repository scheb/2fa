<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\Authentication\RememberMe;

use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\Authentication\RememberMe\RememberMeServicesDecorator;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\RememberMe\RememberMeServicesInterface;

class RememberMeServicesDecoratorTest extends TestCase
{
    /**
     * @var MockObject|Request
     */
    private $request;

    /**
     * @var MockObject|Response
     */
    private $response;

    /**
     * @var MockObject|RememberMeServicesInterface
     */
    private $innerRememberMeServices;

    /**
     * @var RememberMeServicesDecorator
     */
    private $decorator;

    protected function setUp(): void
    {
        $this->request = $this->createMock(Request::class);
        $this->response = $this->createMock(Response::class);
        $this->innerRememberMeServices = $this->createMock(RememberMeServicesInterface::class);
        $this->decorator = new RememberMeServicesDecorator($this->innerRememberMeServices);
    }

    /**
     * @test
     */
    public function loginSuccess_noATwoFactorToken_forwardCall()
    {
        $token = $this->createMock(TokenInterface::class);
        $this->innerRememberMeServices
            ->expects($this->once())
            ->method('loginSuccess')
            ->with(
                $this->identicalTo($this->request),
                $this->identicalTo($this->response),
                $this->identicalTo($token)
            );

        $this->decorator->loginSuccess($this->request, $this->response, $token);
    }

    /**
     * @test
     */
    public function loginSuccess_isTwoFactorToken_setRememberMeAttribute()
    {
        $token = $this->createMock(TwoFactorTokenInterface::class);

        $responseCallback = function ($argument) {
            /* @var Response $argument */
            $this->assertInstanceOf(Response::class, $argument);
            $this->assertFalse($argument === $this->response, 'Response objects must NOT be identical');
            $argument->headers->setCookie(new Cookie('name', 'value'));

            return true;
        };

        $this->innerRememberMeServices
            ->expects($this->once())
            ->method('loginSuccess')
            ->with(
                $this->identicalTo($this->request),
                $this->callback($responseCallback), // 2nd argument is a different Response instance
                $this->identicalTo($token)
            );

        $token
            ->expects($this->once())
            ->method('setAttribute')
            ->with(TwoFactorTokenInterface::ATTRIBUTE_NAME_REMEMBER_ME_COOKIE, $this->callback(function ($argument) {
                $this->assertContainsOnlyInstancesOf(Cookie::class, $argument);

                return true;
            }));

        $this->decorator->loginSuccess($this->request, $this->response, $token);
    }
}
