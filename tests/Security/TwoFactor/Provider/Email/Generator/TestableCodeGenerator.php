<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Provider\Email\Generator;

use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Email\Generator\CodeGenerator;

/**
 * Make the AuthCodeManager class testable.
 */
class TestableCodeGenerator extends CodeGenerator
{
    public int $testCode;
    public int $lastMin;
    public int $lastMax;

    protected function generateCode(int $min, int $max): int
    {
        $this->lastMin = $min;
        $this->lastMax = $max;

        return $this->testCode;
    }
}
