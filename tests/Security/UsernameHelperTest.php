<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security;

use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\UsernameHelper;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UsernameHelperTest extends TestCase
{
    protected function setUp(): void
    {
        $this->requireAtLeastSymfony5_3();
    }

    private function createMockWithMethod(string $className, array $addMethods): MockObject
    {
        return $this->getMockBuilder($className)->addMethods($addMethods)->getMockForAbstractClass();
    }

    /**
     * @test
     */
    public function getTokenUsername_missingMethods_throwRuntimeException(): void
    {
        $token = $this->createMockWithMethod(TokenInterface::class, []);

        $this->expectException(\RuntimeException::class);
        UsernameHelper::getTokenUsername($token);
    }

    /**
     * @test
     */
    public function getTokenUsername_getUsernameExists_returnGetUsernameValue(): void
    {
        $token = $this->createMockWithMethod(TokenInterface::class, ['getUsername']);
        $token
            ->expects($this->any())
            ->method('getUsername')
            ->willReturn('getUsernameValue');

        $returnValue = UsernameHelper::getTokenUsername($token);
        $this->assertEquals('getUsernameValue', $returnValue);
    }

    /**
     * @test
     */
    public function getTokenUsername_getUserIdentifierExists_returnGetUserIdentifierValue(): void
    {
        $token = $this->createMockWithMethod(TokenInterface::class, ['getUserIdentifier']);
        $token
            ->expects($this->any())
            ->method('getUserIdentifier')
            ->willReturn('getUserIdentifierValue');

        $returnValue = UsernameHelper::getTokenUsername($token);
        $this->assertEquals('getUserIdentifierValue', $returnValue);
    }

    /**
     * @test
     */
    public function getUserUsername_missingMethods_throwRuntimeException(): void
    {
        $user = $this->createMockWithMethod(UserInterface::class, []);

        $this->expectException(\RuntimeException::class);
        UsernameHelper::getUserUsername($user);
    }

    /**
     * @test
     */
    public function getUserUsername_getUsernameExists_returnGetUsernameValue(): void
    {
        $user = $this->createMockWithMethod(UserInterface::class, ['getUsername']);
        $user
            ->expects($this->any())
            ->method('getUsername')
            ->willReturn('getUsernameValue');

        $returnValue = UsernameHelper::getUserUsername($user);
        $this->assertEquals('getUsernameValue', $returnValue);
    }

    /**
     * @test
     */
    public function getUserUsername_getUserIdentifierExists_returnGetUserIdentifierValue(): void
    {
        $user = $this->createMockWithMethod(UserInterface::class, ['getUserIdentifier']);
        $user
            ->expects($this->any())
            ->method('getUserIdentifier')
            ->willReturn('getUserIdentifierValue');

        $returnValue = UsernameHelper::getUserUsername($user);
        $this->assertEquals('getUserIdentifierValue', $returnValue);
    }
}
