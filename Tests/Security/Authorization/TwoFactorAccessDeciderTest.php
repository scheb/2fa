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
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\Logout\LogoutUrlGenerator;

class TwoFactorAccessDeciderTest extends TestCase
{
    private const LOGOUT_PATH = '/logout';

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

        // Stub an access rule
        $this->accessMap
            ->expects($this->any())
            ->method('getPatterns')
            ->with($this->request)
            ->willReturn([[TwoFactorInProgressVoter::IS_AUTHENTICATED_2FA_IN_PROGRESS], 'https']);

        // Stub the logout path
        $this->logoutUrlGenerator
            ->expects($this->any())
            ->method('getLogoutPath')
            ->willReturn(self::LOGOUT_PATH);

        $this->accessDecider = new TwoFactorAccessDecider($this->accessMap, $this->accessDecisionManager, $this->httpUtils, $this->logoutUrlGenerator);
    }

    private function whenPathAccess(bool $accessGranted): void
    {
        $this->accessDecisionManager
            ->expects($this->any())
            ->method('decide')
            ->with($this->isInstanceOf(TokenInterface::class), [TwoFactorInProgressVoter::IS_AUTHENTICATED_2FA_IN_PROGRESS], $this->request)
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
        $this->whenPathAccess(true);
        $this->whenIsLogoutPath(false);

        $returnValue = $this->accessDecider->isAccessible($this->request, $this->token);
        $this->assertTrue($returnValue);
    }

    /**
     * @test
     */
    public function isAccessible_isLogoutPath_returnTrue(): void
    {
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
        $this->whenPathAccess(false);
        $this->whenIsLogoutPath(false);

        $returnValue = $this->accessDecider->isAccessible($this->request, $this->token);
        $this->assertFalse($returnValue);
    }
}
