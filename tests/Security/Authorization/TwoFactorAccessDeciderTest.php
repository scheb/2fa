<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\Authorization;

use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\Authorization\TwoFactorAccessDecider;
use Scheb\TwoFactorBundle\Security\Authorization\Voter\TwoFactorInProgressVoter;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Http\AccessMapInterface;
use Symfony\Component\Security\Http\Firewall\AccessListener;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\Logout\LogoutUrlGenerator;

class TwoFactorAccessDeciderTest extends TestCase
{
    private const BASE_URL = '/app_dev.php';
    private const LOGOUT_PATH = '/logout';
    private const LOGOUT_PATH_WITH_BASE_URL = self::BASE_URL.self::LOGOUT_PATH;
    private const ACCESS_MAP_ATTRIBUTES = [TwoFactorInProgressVoter::IS_AUTHENTICATED_2FA_IN_PROGRESS];

    /**
     * @var MockObject|Request
     */
    private $request;

    /**
     * @var MockObject|TokenInterface
     */
    private $token;

    /**
     * @var MockObject|AccessMapInterface
     */
    private $accessMap;

    /**
     * @var string[]
     */
    private $attributes;

    /**
     * @var MockObject|AccessDecisionManagerInterface
     */
    private $accessDecisionManager;

    /**
     * @var MockObject|HttpUtils
     */
    private $httpUtils;

    /**
     * @var MockObject|LogoutUrlGenerator
     */
    private $logoutUrlGenerator;

    /**
     * @var TwoFactorAccessDecider
     */
    private $accessDecider;

    protected function setUp(): void
    {
        $this->request = $this->createMock(Request::class);
        $this->token = $this->createMock(TokenInterface::class);
        $this->accessMap = $this->createMock(AccessMapInterface::class);
        $this->accessDecisionManager = $this->createMock(AccessDecisionManagerInterface::class);
        $this->httpUtils = $this->createMock(HttpUtils::class);
        $this->logoutUrlGenerator = $this->createMock(LogoutUrlGenerator::class);
        $this->accessDecider = new TwoFactorAccessDecider($this->accessMap, $this->accessDecisionManager, $this->httpUtils, $this->logoutUrlGenerator);
    }

    private function stubAccessMapReturnsAttributes(array $attributes): void
    {
        $this->attributes = $attributes;
        $this->accessMap
            ->expects($this->any())
            ->method('getPatterns')
            ->with($this->request)
            ->willReturn([$attributes, 'https']);
    }

    private function whenGeneratedLogoutPath(string $generatedLogoutPath): void
    {
        $this->logoutUrlGenerator
            ->expects($this->any())
            ->method('getLogoutPath')
            ->willReturn($generatedLogoutPath);
    }

    private function whenRequestBaseUrl(string $baseUrl): void
    {
        $this->request
            ->expects($this->any())
            ->method('getBaseUrl')
            ->willReturn($baseUrl);
    }

    private function whenPathAccess(bool $accessGranted): void
    {
        $this->accessDecisionManager
            ->expects($this->any())
            ->method('decide')
            ->with($this->isInstanceOf(TokenInterface::class), $this->attributes, $this->request)
            ->willReturn($accessGranted);
    }

    private function whenIsLogoutPath(bool $accessGranted): void
    {
        $this->httpUtils
            ->expects($this->any())
            ->method('checkRequestPath')
            ->with($this->request, self::LOGOUT_PATH)
            ->willReturn($accessGranted);
    }

    /**
     * @test
     */
    public function isAccessible_pathAccessGranted_returnTrue(): void
    {
        $this->stubAccessMapReturnsAttributes(self::ACCESS_MAP_ATTRIBUTES);
        $this->whenPathAccess(true);
        $this->whenIsLogoutPath(false);

        $returnValue = $this->accessDecider->isAccessible($this->request, $this->token);
        $this->assertTrue($returnValue);
    }

    /**
     * @test
     */
    public function isAccessible_isPublic_returnTrue(): void
    {
        $this->requireAtLeastSymfony5_1();

        $this->stubAccessMapReturnsAttributes([AccessListener::PUBLIC_ACCESS]);
        $this->whenRequestBaseUrl('');
        $this->whenGeneratedLogoutPath(self::LOGOUT_PATH);
        $this->whenPathAccess(false);
        $this->whenIsLogoutPath(false);

        $returnValue = $this->accessDecider->isAccessible($this->request, $this->token);
        $this->assertTrue($returnValue);
    }

    /**
     * @test
     */
    public function isAccessible_isLogoutPathNoBasePath_returnTrue(): void
    {
        $this->stubAccessMapReturnsAttributes(self::ACCESS_MAP_ATTRIBUTES);
        $this->whenRequestBaseUrl('');
        $this->whenGeneratedLogoutPath(self::LOGOUT_PATH);
        $this->whenPathAccess(false);
        $this->whenIsLogoutPath(true);

        $returnValue = $this->accessDecider->isAccessible($this->request, $this->token);
        $this->assertTrue($returnValue);
    }

    /**
     * @test
     */
    public function isAccessible_isLogoutPathWithBasePath_returnTrue(): void
    {
        $this->stubAccessMapReturnsAttributes(self::ACCESS_MAP_ATTRIBUTES);
        $this->whenRequestBaseUrl(self::BASE_URL);
        $this->whenGeneratedLogoutPath(self::LOGOUT_PATH_WITH_BASE_URL);
        $this->whenPathAccess(false);
        $this->whenIsLogoutPath(true);

        $returnValue = $this->accessDecider->isAccessible($this->request, $this->token);
        $this->assertTrue($returnValue);
    }

    /**
     * @test
     */
    public function isAccessible_isNotAccessible_returnFalse(): void
    {
        $this->stubAccessMapReturnsAttributes(self::ACCESS_MAP_ATTRIBUTES);
        $this->whenRequestBaseUrl('');
        $this->whenGeneratedLogoutPath(self::LOGOUT_PATH);
        $this->whenPathAccess(false);
        $this->whenIsLogoutPath(false);

        $returnValue = $this->accessDecider->isAccessible($this->request, $this->token);
        $this->assertFalse($returnValue);
    }
}
