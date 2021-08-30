<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Model\Webauthn;

use Webauthn\PublicKeyCredentialSource;

interface WebauthnTwoFactorInterface
{
    /**
     * Return true if the user should do Webauthn authentication.
     */
    public function isWebauthnAuthenticationEnabled(): bool;

    /**
     * Return all Public Key Credential Sources associated to the user.
     *
     * @return PublicKeyCredentialSource[]
     */
    public function getWebauthnCredentialSources(): array;

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
