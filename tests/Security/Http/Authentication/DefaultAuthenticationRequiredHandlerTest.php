<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\Http\Authentication;

use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\Http\Authentication\DefaultAuthenticationRequiredHandler;
use Scheb\TwoFactorBundle\Security\TwoFactor\TwoFactorFirewallConfig;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\HttpUtils;

class DefaultAuthenticationRequiredHandlerTest extends TestCase
{
    private const AUTH_FORM_PATH = '/authFormPath';
    private const CHECK_PATH = '/checkPath';
    private const OTHER_PATH = '/otherPath';
    private const FIREWALL_NAME = 'firewallName';

    /**
     * @var MockObject|HttpUtils
     */
    private $httpUtils;

    /**
     * @var MockObject|TwoFactorFirewallConfig
     */
    private $twoFactorFirewallConfig;

    /**
     * @var MockObject|Request
     */
    private $request;

    /**
     * @var DefaultAuthenticationRequiredHandler
     */
    private $handler;

    /**
     * @var MockObject|RedirectResponse
     */
    private $authFormRedirectResponse;

    protected function setUp(): void
    {
        $this->httpUtils = $this->createMock(HttpUtils::class);
        $this->twoFactorFirewallConfig = $this->createMock(TwoFactorFirewallConfig::class);
        $this->twoFactorFirewallConfig
            ->expects($this->any())
            ->method('getFirewallName')
            ->willReturn(self::FIREWALL_NAME);
        $this->twoFactorFirewallConfig
            ->expects($this->any())
            ->method('getAuthFormPath')
            ->willReturn(self::AUTH_FORM_PATH);

        $this->request = $this->createMock(Request::class);

        $this->authFormRedirectResponse = $this->createMock(RedirectResponse::class);
        $this->httpUtils
            ->expects($this->any())
            ->method('createRedirectResponse')
            ->with($this->request, self::AUTH_FORM_PATH)
            ->willReturn($this->authFormRedirectResponse);

        $this->handler = new DefaultAuthenticationRequiredHandler(
            $this->httpUtils,
            $this->twoFactorFirewallConfig
        );
    }

    private function stubCurrentPathIsCheckPath(bool $isCheckPath): void
    {
        $this->twoFactorFirewallConfig
            ->expects($this->any())
            ->method('isCheckPathRequest')
            ->with($this->request)
            ->willReturn($isCheckPath);

        $this->request
            ->expects($this->any())
            ->method('getUri')
            ->willReturn($isCheckPath ? self::CHECK_PATH : self::OTHER_PATH);
    }

    private function assertSaveTargetUrl(string $targetUrl): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session
            ->expects($this->once())
            ->method('set')
            ->with('_security.firewallName.target_path', $targetUrl);

        $this->stubRequestToSaveTargetUrl($session);
    }

    private function assertNotSaveTargetUrl(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session
            ->expects($this->never())
            ->method('set');

        $this->stubRequestToSaveTargetUrl($session);
    }

    private function stubRequestToSaveTargetUrl($session): void
    {
        $this->request
            ->expects($this->any())
            ->method('getSession')
            ->willReturn($session);

        // Conditions to store target URL
        $this->request
            ->expects($this->any())
            ->method('hasSession')
            ->willReturn(true);
        $this->request
            ->expects($this->any())
            ->method('isMethodSafe')
            ->willReturn(true);
        $this->request
            ->expects($this->any())
            ->method('isXmlHttpRequest')
            ->willReturn(false);
    }

    /**
     * @test
     */
    public function onAuthenticationRequired_redirectToForm_returnsRedirect(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $this->stubCurrentPathIsCheckPath(false);

        $this->httpUtils
            ->expects($this->once())
            ->method('createRedirectResponse')
            ->with($this->request, self::AUTH_FORM_PATH)
            ->willReturn($this->authFormRedirectResponse);

        $returnValue = $this->handler->onAuthenticationRequired($this->request, $token);
        $this->assertSame($this->authFormRedirectResponse, $returnValue);
    }

    /**
     * @test
     */
    public function onAuthenticationRequired_isNotCheckPath_saveRedirectUrl(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $this->stubCurrentPathIsCheckPath(false);

        $this->assertSaveTargetUrl(self::OTHER_PATH);
        $this->handler->onAuthenticationRequired($this->request, $token);
    }

    /**
     * @test
     */
    public function onAuthenticationRequired_isCheckPath_notSaveRedirectUrl(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $this->stubCurrentPathIsCheckPath(true);

        $this->assertNotSaveTargetUrl();
        $this->handler->onAuthenticationRequired($this->request, $token);
    }
}
