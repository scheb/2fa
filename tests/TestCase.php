<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Symfony\Component\HttpKernel\Kernel;

// phpcs:ignore Symfony.NamingConventions.ValidClassName
abstract class TestCase extends PHPUnitTestCase
{
    private const SYMFONY_5_1 = 50100;

    protected function requireSymfony5_1()
    {
        $this->requireSymfonyVersion(self::SYMFONY_5_1);
    }

    private function requireSymfonyVersion(int $version)
    {
        if (Kernel::VERSION_ID < $version) {
            $this->markTestSkipped("This Symfony version doesn't support authenticators.");
        }
    }
}
