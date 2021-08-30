<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Webauthn;

use Psr\Http\Message\ServerRequestInterface;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;

/**
 * @final
 */
class WebauthnAuthenticator implements WebauthnAuthenticatorInterface
{
    /**
     * @var WebauthnFactory
     */
    private $webauthnFactory;

    public function __construct(WebauthnFactory $webauthnFactory)
    {
        $this->webauthnFactory = $webauthnFactory;
    }

    public function getCredentialCreationOptions(PublicKeyCredentialUserEntity $userEntity, array $excludedPublicKeyDescriptors = []): PublicKeyCredentialCreationOptions
    {
        return $this->webauthnFactory->generatePublicKeyCredentialCreationOptions($userEntity, $excludedPublicKeyDescriptors);
    }

    public function getCredentialRequestOptions(array $allowedPublicKeyDescriptors = []): PublicKeyCredentialRequestOptions
    {
        return $this->webauthnFactory->generatePublicKeyCredentialRequestOptions($allowedPublicKeyDescriptors);
    }

    public function loadAndCheckAttestationResponse(string $data, PublicKeyCredentialCreationOptions $publicKeyCredentialCreationOptions, ServerRequestInterface $serverRequest): PublicKeyCredentialSource
    {
        return $this->webauthnFactory->loadAndCheckAttestationResponse($data, $publicKeyCredentialCreationOptions, $serverRequest);
    }

    public function loadAndCheckAssertionResponse(string $data, PublicKeyCredentialRequestOptions $publicKeyCredentialRequestOptions, PublicKeyCredentialUserEntity $userEntity, ServerRequestInterface $serverRequest): PublicKeyCredentialSource
    {
        return $this->webauthnFactory->loadAndCheckAssertionResponse($data, $publicKeyCredentialRequestOptions, $userEntity, $serverRequest);
    }
}
