<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Webauthn;

use Psr\Http\Message\ServerRequestInterface;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialSourceRepository;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\Server;

final class WebauthnFactory
{
    /**
     * @var Server
     */
    private $server;

    public function __construct(PublicKeyCredentialSourceRepository $publicKeyCredentialSourceRepository, string $rpId, string $rpName, ?string $rpIcon)
    {
        $rpEntity = new PublicKeyCredentialRpEntity($rpName, $rpId, $rpIcon);

        $this->server = new Server($rpEntity, $publicKeyCredentialSourceRepository);
    }

    public function generatePublicKeyCredentialCreationOptions(PublicKeyCredentialUserEntity $userEntity, array $excludedPublicKeyDescriptors = []): PublicKeyCredentialCreationOptions
    {
        return $this->server->generatePublicKeyCredentialCreationOptions($userEntity, null, $excludedPublicKeyDescriptors);
    }

    public function loadAndCheckAttestationResponse(string $data, PublicKeyCredentialCreationOptions $publicKeyCredentialCreationOptions, ServerRequestInterface $serverRequest): PublicKeyCredentialSource
    {
        return $this->server->loadAndCheckAttestationResponse($data, $publicKeyCredentialCreationOptions, $serverRequest);
    }

    public function generatePublicKeyCredentialRequestOptions(array $allowedPublicKeyDescriptors = []): PublicKeyCredentialRequestOptions
    {
        return $this->server->generatePublicKeyCredentialRequestOptions(null, $allowedPublicKeyDescriptors);
    }

    public function loadAndCheckAssertionResponse(string $data, PublicKeyCredentialRequestOptions $publicKeyCredentialRequestOptions, PublicKeyCredentialUserEntity $userEntity, ServerRequestInterface $serverRequest): PublicKeyCredentialSource
    {
        return $this->server->loadAndCheckAssertionResponse($data, $publicKeyCredentialRequestOptions, $userEntity, $serverRequest);
    }
}
