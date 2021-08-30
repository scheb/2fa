<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Model\Webauthn;

interface WebauthnTwoFactorInterface
{
    /**
     * Return true if the user should do Webauthn authentication.
     */
    public function isWebauthnAuthenticationEnabled(): bool;

    /**
     * Return the user name.
     */
    public function getWebauthnUsername(): string;

    /**
     * Return the user identifier.
     */
    public function getWebauthnUserId(): string;

    /**
     * Return the user display name.
     */
    public function getWebauthnDisplayName(): string;

    /**
     * Return the user icon/avatar.
     */
    public function getWebauthnIcon(): ?string;
}
