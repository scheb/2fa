<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\Http\EventListener;

use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\AuthenticationMethodProviderInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderInterface;

interface AuthenticationMethodTwoFactorProviderInterface extends TwoFactorProviderInterface, AuthenticationMethodProviderInterface
{
}
