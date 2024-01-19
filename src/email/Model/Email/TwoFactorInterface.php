<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Model\Email;

use DateTimeImmutable;

/**
 * @method DateTimeImmutable|null getEmailAuthCodeCreatedAt()
 * @method void                    setEmailAuthCodeCreatedAt(DateTimeImmutable|null $createdAt)
 */
interface TwoFactorInterface
{
    /**
     * Return true if the user should do two-factor authentication.
     */
    public function isEmailAuthEnabled(): bool;

    /**
     * Return user email address.
     */
    public function getEmailAuthRecipient(): string;

    /**
     * Return the authentication code.
     */
    public function getEmailAuthCode(): string|null;

    /**
     * Set the authentication code.
     */
    public function setEmailAuthCode(string $authCode): void;
}
