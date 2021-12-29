<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Provider;

use Scheb\TwoFactorBundle\Model\PreferredProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Used to mock combined interfaces.
 */
interface UserWithPreferredProviderInterface extends UserInterface, PreferredProviderInterface
{
}
