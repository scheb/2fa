<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Provider\Email;

use DateTimeImmutable;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;

/**
 * Used to mock method annotations.
 */
interface TestableTwoFactorInterface extends TwoFactorInterface
{
    public function getEmailAuthCodeCreatedAt(): DateTimeImmutable|null;

    public function setEmailAuthCodeCreatedAt(DateTimeImmutable|null $createdAt): void;
}
