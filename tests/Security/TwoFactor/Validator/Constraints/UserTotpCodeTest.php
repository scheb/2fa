<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Validator\Constraints;

use PHPUnit\Framework\Attributes\Test;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Mapping\Loader\AttributeLoader;

class UserTotpCodeTest extends TestCase
{
    #[Test]
    public function configureConstraintFromAttribute_configurationIsCorrect(): void
    {
        $metadata = new ClassMetadata(UserTotpCodeDummy::class);
        $this->assertTrue((new AttributeLoader())->loadClassMetadata($metadata));

        [$aConstraint] = $metadata->getPropertyMetadata('a')[0]->getConstraints();
        $this->assertSame('code_invalid', $aConstraint->message);
        $this->assertSame('SchebTwoFactorBundle', $aConstraint->translationDomain);
        $this->assertSame('scheb_two_factor.security.totp.validator.user_totp_code', $aConstraint->validatedBy());

        [$bConstraint] = $metadata->getPropertyMetadata('b')[0]->getConstraints();
        $this->assertSame('myMessage', $bConstraint->message);
        $this->assertSame('myDomain', $bConstraint->translationDomain);
        $this->assertSame('my_service', $bConstraint->validatedBy());
        $this->assertSame(['Default', 'UserTotpCodeDummy'], $bConstraint->groups);
        $this->assertNull($bConstraint->payload);

        [$cConstraint] = $metadata->getPropertyMetadata('c')[0]->getConstraints();
        $this->assertSame(['my_group'], $cConstraint->groups);
        $this->assertSame('some attached data', $cConstraint->payload);

        // Backwards compatibility for Symfony 7.4
        // @phpcs:ignore SlevomatCodingStandard.ControlStructures.EarlyExit.EarlyExitNotUsed
        if (Kernel::VERSION_ID < 80000) {
            [$cConstraint] = $metadata->getPropertyMetadata('c74')[0]->getConstraints();
            $this->assertSame(['my_group'], $cConstraint->groups);
            $this->assertSame('some attached data', $cConstraint->payload);
        }
    }
}
