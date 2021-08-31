<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Webauthn;

use Nyholm\Psr7\Factory\Psr17Factory;
use Scheb\TwoFactorBundle\Model\Webauthn\WebauthnTwoFactorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorFormRendererInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Throwable;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;

/**
 * @final
 */
class WebauthnAuthenticatorTwoFactorProvider implements TwoFactorProviderInterface
{
    private const WEBAUTHN_SESSION_PARAMETER_NAME = 'webauthn_credential_reauest_options'; //Hardcode to be removed in favor of a configuration parameter

    /**
     * @var WebauthnAuthenticatorInterface
     */
    private $authenticator;

    /**
     * @var TwoFactorFormRendererInterface
     */
    private $formRenderer;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var PsrHttpFactory
     */
    private $messageConverter;

    public function __construct(WebauthnAuthenticatorInterface $authenticator, TwoFactorFormRendererInterface $formRenderer, RequestStack $requestStack)
    {
        $this->authenticator = $authenticator;
        $this->formRenderer = $formRenderer;
        $this->requestStack = $requestStack;
        $psr17Factory = new Psr17Factory();
        $this->messageConverter = new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
    }

    public function beginAuthentication(AuthenticationContextInterface $context): bool
    {
        $user = $context->getUser();

        return $user instanceof WebauthnTwoFactorInterface && $user->isWebauthnAuthenticationEnabled();
    }

    public function prepareAuthentication($user): void
    {
        if (!($user instanceof WebauthnTwoFactorInterface)) {
            throw new \LogicException('Invalid user');
        }
        $credentialSources = $user->getWebauthnCredentialSources();
        $allowedCredentials = array_map(static function (PublicKeyCredentialSource $credential) {
            return $credential->getPublicKeyCredentialDescriptor();
        }, $credentialSources);

        $options = $this->authenticator->getCredentialRequestOptions($allowedCredentials);

        $sfRequest = $this->getRequest();
        $sfRequest->getSession()->set(self::WEBAUTHN_SESSION_PARAMETER_NAME, $options);
    }

    public function validateAuthenticationCode($user, string $authenticationCode): bool
    {
        if (!($user instanceof WebauthnTwoFactorInterface)) {
            return false;
        }

        $sfRequest = $this->getRequest();
        $publicKeyCredentialRequestOptions = $sfRequest->getSession()->remove(self::WEBAUTHN_SESSION_PARAMETER_NAME);
        if (null === $publicKeyCredentialRequestOptions) {
            throw new \LogicException('No public key credential reauest options available');
        }
        $psrRequest = $this->messageConverter->createRequest($sfRequest);
        $userEntity = $this->createUserEntity($user);

        try {
            $this->authenticator->loadAndCheckAssertionResponse($authenticationCode, $publicKeyCredentialRequestOptions, $userEntity, $psrRequest);
        } catch (Throwable $e) {
            //Log the error here
            return false;
        }

        return true;
    }

    public function getFormRenderer(): TwoFactorFormRendererInterface
    {
        return $this->formRenderer;
    }

    private function getRequest(): Request
    {
        $sfRequest = $this->requestStack->getMainRequest();
        if (null === $sfRequest) {
            throw new \LogicException('No request available');
        }

        return $sfRequest;
    }

    private function createUserEntity(WebauthnTwoFactorInterface $user): PublicKeyCredentialUserEntity
    {
        return new PublicKeyCredentialUserEntity(
            $user->getWebauthnUsername(),
            $user->getWebauthnUserId(),
            $user->getWebauthnDisplayName(),
            $user->getWebauthnIcon()
        );
    }
}
