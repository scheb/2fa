<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\Http\Authentication;

use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\Http\Authentication\DefaultAuthenticationFailureHandler;
use Scheb\TwoFactorBundle\Security\TwoFactor\TwoFactorFirewallConfig;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\HttpUtils;

class DefaultAuthenticationFailureHandlerTest extends TestCase
{
    private const AUTH_FORM_PATH = '/auth_form_path';

    /**
     * @var MockObject|HttpUtils
     */
    private $httpUtils;

    /**
     * @var MockObject|TwoFactorFirewallConfig
     */
    private $twoFactorFirewallConfig;

    /**
     * @var DefaultAuthenticationFailureHandler
     */
    private $failureHandler;

    /**
     * @var MockObject|Request
     */
    private $request;

    /**
     * @var MockObject|SessionInterface
     */
    private $session;

    protected function setUp(): void
    {
        $this->httpUtils = $this->createMock(HttpUtils::class);
        $this->twoFactorFirewallConfig = $this->createMock(TwoFactorFirewallConfig::class);
        $this->twoFactorFirewallConfig
            ->expects($this->any())
            ->method('getAuthFormPath')
            ->willReturn(self::AUTH_FORM_PATH);

        $this->failureHandler = new DefaultAuthenticationFailureHandler($this->httpUtils, $this->twoFactorFirewallConfig);

        $this->session = $this->createMock(SessionInterface::class);
        $this->request = $this->createMock(Request::class);
        $this->request
            ->expects($this->any())
            ->method('getSession')
            ->willReturn($this->session);
    }

    /**
     * @test
     */
    public function onAuthenticationFailure_authenticationExceptionGiven_setExceptionMessageInSession(): void
    {
        $authenticationException = new AuthenticationException('Exception message');

        $this->httpUtils
            ->expects($this->any())
            ->method('createRedirectResponse')
            ->willReturn($this->createMock(Response::class));

        $this->session
            ->expects($this->once())
            ->method('set')
            ->with(Security::AUTHENTICATION_ERROR, $authenticationException);

        $this->failureHandler->onAuthenticationFailure($this->request, $authenticationException);
    }

    /**
     * @test
     */
    public function onAuthenticationFailure_failedAuthentication_redirectToAuthenticationForm(): void
    {
        $redirectResponse = $this->createMock(RedirectResponse::class);
        $this->httpUtils
            ->expects($this->once())
            ->method('createRedirectResponse')
            ->with($this->request, self::AUTH_FORM_PATH)
            ->willReturn($redirectResponse);

        $returnValue = $this->failureHandler->onAuthenticationFailure($this->request, new AuthenticationException());
        $this->assertSame($redirectResponse, $returnValue);
    }
}
