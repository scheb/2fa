<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Webauthn;

use Psr\Http\Message\ServerRequestInterface;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;

interface WebauthnAuthenticatorInterface
{
    public function getCredentialRequestOptions(array $allowedPublicKeyDescriptors = []): PublicKeyCredentialRequestOptions;

    public function loadAndCheckAttestationResponse(string $data, PublicKeyCredentialCreationOptions $publicKeyCredentialCreationOptions, ServerRequestInterface $serverRequest): PublicKeyCredentialSource;

    public function getCredentialCreationOptions(PublicKeyCredentialUserEntity $userEntity, array $excludedPublicKeyDescriptors = []): PublicKeyCredentialCreationOptions;

    public function loadAndCheckAssertionResponse(string $data, PublicKeyCredentialRequestOptions $publicKeyCredentialRequestOptions, PublicKeyCredentialUserEntity $userEntity, ServerRequestInterface $serverRequest): PublicKeyCredentialSource;
}
