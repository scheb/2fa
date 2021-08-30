<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Webauthn;

use Psr\Http\Message\ServerRequestInterface;
use Scheb\TwoFactorBundle\Model\Webauthn\WebauthnTwoFactorInterface;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialSource;

interface WebauthnAuthenticatorInterface
{
    /**
     * Generate the Credential Request Options for the user.
     */
    public function getCredentialRequestOptions(array $allowedPublicKeyDescriptors = []): PublicKeyCredentialRequestOptions;

    /**
     * Load and Check the Attestation Response.
     */
    public function loadAndCheckAttestationResponse(string $data, PublicKeyCredentialCreationOptions $publicKeyCredentialCreationOptions, ServerRequestInterface $serverRequest): PublicKeyCredentialSource;
}
