<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Symfony\Component\HttpKernel\Kernel;

// phpcs:ignore Symfony.NamingConventions.ValidClassName
abstract class TestCase extends PHPUnitTestCase
{
    private const SYMFONY_5_1 = 50100;
    private const SYMFONY_5_3 = 50300;

    protected function requireAtLeastSymfony5_1()
    {
        $this->requireAtLeastSymfonyVersion(self::SYMFONY_5_1);
    }

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
}
