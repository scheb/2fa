<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\Http\Authentication;

use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\Http\Authentication\DefaultAuthenticationSuccessHandler;
use Scheb\TwoFactorBundle\Security\TwoFactor\TwoFactorFirewallConfig;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\HttpUtils;

class DefaultAuthenticationSuccessHandlerTest extends TestCase
{
    private const FIREWALL_NAME = 'firewallName';
    private const DEFAULT_TARGET_PATH = '/defaultTargetPath';
    private const SESSION_TARGET_PATH = '/sessionTargetPath';

    /**
     * @var MockObject|HttpUtils
     */
    private $httpUtils;

    /**
     * @var MockObject|TwoFactorFirewallConfig
     */
    private $twoFactorFirewallConfig;

    /**
     * @var DefaultAuthenticationSuccessHandler
     */
    private $successHandler;

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
            ->method('getFirewallName')
            ->willReturn(self::FIREWALL_NAME);

        $this->session = $this->createMock(SessionInterface::class);
        $this->request = $this->createMock(Request::class);
        $this->request
            ->expects($this->any())
            ->method('getSession')
            ->willReturn($this->session);
    }

    private function setUpSuccessHandlerWithOptions(bool $alwaysUseDefaultTargetPath): void
    {
        $this->twoFactorFirewallConfig
            ->expects($this->any())
            ->method('isAlwaysUseDefaultTargetPath')
            ->willReturn($alwaysUseDefaultTargetPath);
        $this->twoFactorFirewallConfig
            ->expects($this->any())
            ->method('getDefaultTargetPath')
            ->willReturn(self::DEFAULT_TARGET_PATH);

        $this->successHandler = new DefaultAuthenticationSuccessHandler($this->httpUtils, $this->twoFactorFirewallConfig);
    }

    private function stubSessionHasTargetPath(string $sessionTargetPath): void
    {
        $this->session
            ->expects($this->any())
            ->method('get')
            ->with('_security.firewallName.target_path')
            ->willReturn($sessionTargetPath);
    }

    private function assertCreateRedirectTo(string $targetPath): RedirectResponse
    {
        $redirectResponse = $this->createMock(RedirectResponse::class);
        $this->httpUtils
            ->expects($this->once())
            ->method('createRedirectResponse')
            ->with($this->request, $targetPath)
            ->willReturn($redirectResponse);

        return $redirectResponse;
    }

    /**
     * @test
     */
    public function onAuthenticationSuccess_hasAuthenticationException_removeAuthenticationException(): void
    {
        $this->setUpSuccessHandlerWithOptions(false);

        $this->httpUtils
            ->expects($this->any())
            ->method('createRedirectResponse')
            ->willReturn($this->createMock(RedirectResponse::class));

        $this->session
            ->expects($this->once())
            ->method('remove')
            ->with(Security::AUTHENTICATION_ERROR);

        $token = $this->createMock(TokenInterface::class);
        $this->successHandler->onAuthenticationSuccess($this->request, $token);
    }

    /**
     * @test
     */
    public function onAuthenticationSuccess_alwaysUseDefaultTargetPath_redirectToDefaultTargetPath(): void
    {
        $this->setUpSuccessHandlerWithOptions(true);
        $this->stubSessionHasTargetPath(self::SESSION_TARGET_PATH);

        $redirectResponse = $this->assertCreateRedirectTo(self::DEFAULT_TARGET_PATH);

        $returnValue = $this->successHandler->onAuthenticationSuccess($this->request, $this->createMock(TokenInterface::class));
        $this->assertSame($redirectResponse, $returnValue);
    }

    /**
     * @test
     */
    public function onAuthenticationSuccess_hasTargetPathInSession_redirectToSessionTargetPath(): void
    {
        $this->setUpSuccessHandlerWithOptions(false);
        $this->stubSessionHasTargetPath(self::SESSION_TARGET_PATH);

        $redirectResponse = $this->assertCreateRedirectTo(self::SESSION_TARGET_PATH);

        $returnValue = $this->successHandler->onAuthenticationSuccess($this->request, $this->createMock(TokenInterface::class));
        $this->assertSame($redirectResponse, $returnValue);
    }

    /**
     * @test
     */
    public function onAuthenticationSuccess_noTargetPathInSession_redirectToDefaultTargetPath(): void
    {
        $this->setUpSuccessHandlerWithOptions(false);

        $redirectResponse = $this->assertCreateRedirectTo(self::DEFAULT_TARGET_PATH);

        $returnValue = $this->successHandler->onAuthenticationSuccess($this->request, $this->createMock(TokenInterface::class));
        $this->assertSame($redirectResponse, $returnValue);
    }
}
