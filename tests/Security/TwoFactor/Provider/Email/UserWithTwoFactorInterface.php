<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Provider\Email;

use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Used to mock combined interfaces.
 */
interface UserWithTwoFactorInterface extends UserInterface, TwoFactorInterface
{
}
