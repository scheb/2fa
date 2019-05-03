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

class TwoFactorAccessDeciderTest extends TestCase
{
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
     * @var TwoFactorAccessDecider
     */
    private $accessDecider;

    protected function setUp(): void
    {
        $this->request = $this->createMock(Request::class);
        $this->token = $this->createMock(TokenInterface::class);
        $this->accessMap = $this->createMock(AccessMapInterface::class);
        $this->accessDecisionManager = $this->createMock(AccessDecisionManagerInterface::class);

        // Stub an access rule
        $this->accessMap
            ->expects($this->any())
            ->method('getPatterns')
            ->with($this->request)
            ->willReturn([[TwoFactorInProgressVoter::IS_AUTHENTICATED_2FA_IN_PROGRESS], 'https']);

        $this->accessDecider = new TwoFactorAccessDecider($this->accessMap, $this->accessDecisionManager);
    }

    private function whenPathAccessIsChecked(bool $accessGranted): void
    {
        $this->accessDecisionManager
            ->expects($this->any())
            ->method('decide')
            ->with($this->isInstanceOf(TokenInterface::class), [TwoFactorInProgressVoter::IS_AUTHENTICATED_2FA_IN_PROGRESS], $this->request)
            ->willReturn($accessGranted);
    }

    /**
     * @test
     */
    public function isAccessible_pathAccessGranted_returnTrue(): void
    {
        $this->whenPathAccessIsChecked(true);
        $returnValue = $this->accessDecider->isAccessible($this->request, $this->token);
        $this->assertTrue($returnValue);
    }

    /**
     * @test
     */
    public function isAccessible_pathAccessDenied_returnFalse(): void
    {
        $this->whenPathAccessIsChecked(false);
        $returnValue = $this->accessDecider->isAccessible($this->request, $this->token);
        $this->assertFalse($returnValue);
    }
}
