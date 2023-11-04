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
    public function getTokenUsername_getUserIdentifierExistsInSymfony60_returnGetUserIdentifierValue(): void
    {
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
    public function getUserUsername_getUserIdentifierExistsInSymfony60_returnGetUserIdentifierValue(): void
    {
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
