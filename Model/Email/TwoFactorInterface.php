<?php

namespace Scheb\TwoFactorBundle\Model\Email;

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
    public function getEmailAuthCode(): string;

    /**
     * Set the authentication code.
     */
    public function setEmailAuthCode(string $authCode): void;
}
