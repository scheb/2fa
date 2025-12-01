<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Validator\Constraints;

use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Scheb\TwoFactorBundle\Security\TwoFactor\Validator\Constraints\UserTotpCode;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Mapping\Loader\AttributeLoader;

class UserTotpCodeTest extends TestCase
{
    #[Test]
    public function validatedBy_objectInitialized_returnDefault(): void
    {
        $constraint = new UserTotpCode();

        $this->assertSame('scheb_two_factor.security.totp.validator.user_totp_code', $constraint->validatedBy());
    }

    #[Test]
    #[DataProvider('provideServiceValidatedConstraints')]
    public function validatedBy_customValue_returnValid(UserTotpCode $constraint): void
    {
        $this->assertSame('my_service', $constraint->validatedBy());
    }

    /**
     * @return Generator<string, list{UserTotpCode}>
     */
    public static function provideServiceValidatedConstraints(): iterable
    {
        yield 'Doctrine style' => [new UserTotpCode(['service' => 'my_service'])];

        yield 'named arguments' => [new UserTotpCode(service: 'my_service')];

        $metadata = new ClassMetadata(UserTotpCodeDummy::class);
        self::assertTrue((new AttributeLoader())->loadClassMetadata($metadata));

        yield 'attribute' => [$metadata->getPropertyMetadata('b')[0]->getConstraints()[0]];
    }

    #[Test]
    public function configureConstraintFromAttribute_configurationIsCorrect(): void
    {
        $metadata = new ClassMetadata(UserTotpCodeDummy::class);
        $this->assertTrue((new AttributeLoader())->loadClassMetadata($metadata));

        [$bConstraint] = $metadata->getPropertyMetadata('b')[0]->getConstraints();
        $this->assertSame('myMessage', $bConstraint->message);
        $this->assertSame('myDomain', $bConstraint->translationDomain);
        $this->assertSame(['Default', 'UserTotpCodeDummy'], $bConstraint->groups);
        $this->assertNull($bConstraint->payload);

        [$cConstraint] = $metadata->getPropertyMetadata('c')[0]->getConstraints();
        $this->assertSame(['my_group'], $cConstraint->groups);
        $this->assertSame('some attached data', $cConstraint->payload);
    }
}
