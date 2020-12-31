<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\DependencyInjection\Factory\Security;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AuthenticatorFactoryInterface;

/**
 * @internal Compatibility with authenticators in Symfony >= 5.1
 */
class AuthenticatorTwoFactorFactory extends TwoFactorFactory implements AuthenticatorFactoryInterface
{
}
