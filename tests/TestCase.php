<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Symfony\Component\HttpKernel\Kernel;

// phpcs:ignore Symfony.NamingConventions.ValidClassName
abstract class TestCase extends PHPUnitTestCase
{
    private const MINOR_VERSION_INCREMENT = 100;
    private const SYMFONY_5_4 = 50400;
    private const SYMFONY_6_0 = 60000;

    protected function requireAtMostSymfony5_4(): void
    {
        $this->requireAtMostSymfonyVersion(self::SYMFONY_5_4);
    }

    private function requireAtMostSymfonyVersion(int $version): void
    {
        if (Kernel::VERSION_ID >= ($version + self::MINOR_VERSION_INCREMENT)) {
            $this->markTestSkipped('Skipping test case, minimum required Symfony version not fulfilled.');
        }
    }

    protected function requireAtLeastSymfony6_0(): void
    {
        $this->requireAtLeastSymfonyVersion(self::SYMFONY_6_0);
    }

    private function requireAtLeastSymfonyVersion(int $version): void
    {
        if (Kernel::VERSION_ID < $version) {
            $this->markTestSkipped('Skipping test case, minimum required Symfony version not fulfilled.');
        }
    }
}
