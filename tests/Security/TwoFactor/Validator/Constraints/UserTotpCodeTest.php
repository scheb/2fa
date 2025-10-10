<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Validator\Constraints;

use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use Scheb\TwoFactorBundle\Security\TwoFactor\Validator\Constraints\UserTotpCode;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Mapping\Loader\AttributeLoader;

class UserTotpCodeTest extends TestCase
{
    public function testValidatedByStandardValidator(): void
    {
        $constraint = new UserTotpCode();

        self::assertSame('scheb_two_factor.security.totp.validator.user_totp_code', $constraint->validatedBy());
    }

    #[DataProvider('provideServiceValidatedConstraints')]
    public function testValidatedByService(UserTotpCode $constraint): void
    {
        self::assertSame('my_service', $constraint->validatedBy());
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

    public function testAttributes(): void
    {
        $metadata = new ClassMetadata(UserTotpCodeDummy::class);
        self::assertTrue((new AttributeLoader())->loadClassMetadata($metadata));

        [$bConstraint] = $metadata->getPropertyMetadata('b')[0]->getConstraints();
        self::assertSame('myMessage', $bConstraint->message);
        self::assertSame('myDomain', $bConstraint->translationDomain);
        self::assertSame(['Default', 'UserTotpCodeDummy'], $bConstraint->groups);
        self::assertNull($bConstraint->payload);

        [$cConstraint] = $metadata->getPropertyMetadata('c')[0]->getConstraints();
        self::assertSame(['my_group'], $cConstraint->groups);
        self::assertSame('some attached data', $cConstraint->payload);
    }
}
