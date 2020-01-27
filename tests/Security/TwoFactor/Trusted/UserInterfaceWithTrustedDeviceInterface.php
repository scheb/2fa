<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Trusted;

use Scheb\TwoFactorBundle\Model\TrustedDeviceInterface;
use Symfony\Component\Security\Core\User\UserInterface;

abstract class UserInterfaceWithTrustedDeviceInterface implements UserInterface, TrustedDeviceInterface
{
}
