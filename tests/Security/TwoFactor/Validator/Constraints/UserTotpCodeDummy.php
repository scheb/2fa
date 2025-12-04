<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Validator\Constraints;

use Scheb\TwoFactorBundle\Security\TwoFactor\Validator\Constraints\UserTotpCode;

class UserTotpCodeDummy
{
    #[UserTotpCode]
    public string $a;

    #[UserTotpCode(message: 'myMessage', translationDomain: 'myDomain', service: 'my_service')]
    public string $b;

    #[UserTotpCode(groups: ['my_group'], payload: 'some attached data')]
    public string $c;

    // Test backwards compatibility with Symfony 7.4
    // Associative arrays are only supported under Symfony 7.4
    #[UserTotpCode(['groups' => ['my_group'], 'payload' => 'some attached data'])]
    public string $c74;
}
