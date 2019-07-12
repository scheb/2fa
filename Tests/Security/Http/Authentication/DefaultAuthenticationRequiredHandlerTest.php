<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\Http\Authentication;

use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\Http\Authentication\DefaultAuthenticationRequiredHandler;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\HttpUtils;

class DefaultAuthenticationRequiredHandlerTest extends TestCase
{
    private const FORM_PATH = '/form_path';
    private const CHECK_PATH = '/check_path';
    private const FIREWALL_NAME = 'firewallName';

    /**
     * @var MockObject|HttpUtils
     */
    private $httpUtils;

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
        $this->request = $this->createMock(Request::class);

        $this->authFormRedirectResponse = $this->createMock(RedirectResponse::class);
        $this->httpUtils
            ->expects($this->any())
            ->method('createRedirectResponse')
            ->with($this->request, self::FORM_PATH)
            ->willReturn($this->authFormRedirectResponse);

        $options = [
            'auth_form_path' => self::FORM_PATH,
            'check_path' => self::CHECK_PATH,
        ];

        $this->handler = new DefaultAuthenticationRequiredHandler(
            $this->httpUtils,
            self::FIREWALL_NAME,
            $options
        );
    }

    private function stubCurrentPath(string $currentPath): void
    {
        $this->request
            ->expects($this->any())
            ->method('getUri')
            ->willReturn($currentPath);

        $this->httpUtils
            ->expects($this->any())
            ->method('checkRequestPath')
            ->with($this->request)
            ->willReturnCallback(function ($request, $pathToCheck) use ($currentPath) {
                return $currentPath === $pathToCheck;
            });
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
        $this->stubCurrentPath('/somePath');

        $this->httpUtils
            ->expects($this->once())
            ->method('createRedirectResponse')
            ->with($this->request, self::FORM_PATH)
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
        $this->stubCurrentPath('/somePath');

        $this->assertSaveTargetUrl('/somePath');
        $this->handler->onAuthenticationRequired($this->request, $token);
    }

    /**
     * @test
     */
    public function onAuthenticationRequired_isCheckPath_notSaveRedirectUrl(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $this->stubCurrentPath(self::CHECK_PATH);

        $this->assertNotSaveTargetUrl();
        $this->handler->onAuthenticationRequired($this->request, $token);
    }
}
