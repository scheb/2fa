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
    /**
     * @param string[] $addMethods
     */
    private function createMockWithExtraMethod(string $className, array $addMethods): MockObject
    {
        return $this->getMockBuilder($className)->addMethods($addMethods)->getMockForAbstractClass();
    }

    /**
     * @test
     */
    public function getTokenUsername_getUsernameExistsInSymfony54_returnGetUsernameValue(): void
    {
        $this->requireAtMostSymfony5_4();

        $token = $this->createMockWithExtraMethod(TokenInterface::class, []);
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
    public function getTokenUsername_getUserIdentifierImplementedInSymfony54_returnGetUserIdentifierValue(): void
    {
        $this->requireAtMostSymfony5_4();

        $additionalMethods = ['getUserIdentifier'];
        $this->executeTestGetTokenUserNameFromGetUserIdentifier($additionalMethods);
    }

    /**
     * @test
     */
    public function getTokenUsername_getUserIdentifierExistsInSymfony60_returnGetUserIdentifierValue(): void
    {
        $this->requireAtLeastSymfony6_0();

        $additionalMethods = [];
        $this->executeTestGetTokenUserNameFromGetUserIdentifier($additionalMethods);
    }

    /**
     * @param string[] $additionalMethods
     */
    private function executeTestGetTokenUserNameFromGetUserIdentifier(array $additionalMethods): void
    {
        $token = $this->createMockWithExtraMethod(TokenInterface::class, $additionalMethods);
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
    public function getUserUsername_getUsernameExistsInSymfony54_returnGetUsernameValue(): void
    {
        $this->requireAtMostSymfony5_4();

        $user = $this->createMockWithExtraMethod(UserInterface::class, []);
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
    public function getUserUsername_getUserIdentifierImplementedInSymfony54_returnGetUserIdentifierValue(): void
    {
        $this->requireAtMostSymfony5_4();

        $additionalMethods = ['getUserIdentifier'];
        $this->executeTestGetUsernameFromGetUserIdentifier($additionalMethods);
    }

    /**
     * @test
     */
    public function getUserUsername_getUserIdentifierExistsInSymfony60_returnGetUserIdentifierValue(): void
    {
        $this->requireAtLeastSymfony6_0();

        $additionalMethods = [];
        $this->executeTestGetUsernameFromGetUserIdentifier($additionalMethods);
    }

    /**
     * @param string[] $additionalMethods
     */
    private function executeTestGetUsernameFromGetUserIdentifier(array $additionalMethods): void
    {
        $user = $this->createMockWithExtraMethod(UserInterface::class, $additionalMethods);
        $user
            ->expects($this->any())
            ->method('getUserIdentifier')
            ->willReturn('getUserIdentifierValue');

        $returnValue = UsernameHelper::getUserUsername($user);
        $this->assertEquals('getUserIdentifierValue', $returnValue);
    }
}
