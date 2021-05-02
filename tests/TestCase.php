<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

// phpcs:ignore Symfony.NamingConventions.ValidClassName
abstract class TestCase extends PHPUnitTestCase
{
    private const SYMFONY_5_3 = 50300;

    protected function requireAtLeastSymfony5_3()
    {
        $this->requireAtLeastSymfonyVersion(self::SYMFONY_5_3);
    }

    private function requireAtLeastSymfonyVersion(int $version)
    {
        if (Kernel::VERSION_ID < $version) {
            $this->markTestSkipped('Skipping test case, minimum required Symfony version not fulfilled.');
        }
    }

    protected function createMock(string $originalClassName): MockObject
    {
        // Compatibility for Symfony >= 5.3
        // Symfony 5.3 has removed the "getUsername" method from TokenInterface and UserInterface, in favor of a new
        // "getUserIdentifier", which isn't materialized yet, but is only annotated via PHPDoc. Implementations of that
        // interface still have the "getUsername" present for backwards compatibility, together with the new
        // "getUserIdentifier" method.
        // This piece of code aims to mitigate the problem with mocking in a generic way. Adding the missing methods to
        // mocks and adding a default return value to avoid any PHP TypeErrors.
        $reflection = new \ReflectionClass($originalClassName);
        if (Kernel::VERSION_ID >= self::SYMFONY_5_3
            && (
                TokenInterface::class === $originalClassName
                || $reflection->isSubclassOf(TokenInterface::class)
                || UserInterface::class === $originalClassName
                || $reflection->isSubclassOf(UserInterface::class)
            )
        ) {
            $mockBuilder = $this->getMockBuilder($originalClassName)->disableOriginalConstructor();
            if (!method_exists($originalClassName, 'getUserIdentifier')) {
                $mockBuilder->addMethods(['getUserIdentifier']);
            }
            if (!method_exists($originalClassName, 'getUsername')) {
                $mockBuilder->addMethods(['getUsername']);
            }

            if ($reflection->isInterface() || $reflection->isAbstract()) {
                $mock = $mockBuilder->getMockForAbstractClass();
            } else {
                $mock = $mockBuilder->getMock();
            }

            if (!method_exists($originalClassName, 'getUserIdentifier')) {
                $mock
                    ->expects($this->any())
                    ->method('getUserIdentifier')
                    ->willReturn('username');
            }
            if (!method_exists($originalClassName, 'getUsername')) {
                $mock
                    ->expects($this->any())
                    ->method('getUsername')
                    ->willReturn('username');
            }

            return $mock;
        }

        return parent::createMock($originalClassName);
    }
}
