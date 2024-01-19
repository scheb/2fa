<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Provider\Email\Generator;

use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Email\Generator\CodeGeneratorInterface;

interface TestableCodeGeneratorInterface extends CodeGeneratorInterface
{
    public function isCodeExpired(TwoFactorInterface $user): bool;
}
