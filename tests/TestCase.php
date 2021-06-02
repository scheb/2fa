<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Symfony\Component\HttpKernel\Kernel;

// phpcs:ignore Symfony.NamingConventions.ValidClassName
abstract class TestCase extends PHPUnitTestCase
{
    private const SYMFONY_5_3 = 50300;

    protected function requireAtLeastSymfony5_3(): void
    {
        $this->requireAtLeastSymfonyVersion(self::SYMFONY_5_3);
    }

    private function requireAtLeastSymfonyVersion(int $version): void
    {
        if (Kernel::VERSION_ID < $version) {
            $this->markTestSkipped('Skipping test case, minimum required Symfony version not fulfilled.');
        }
    }
}
