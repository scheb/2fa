<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google;

use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorFormRendererInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderInterface;

/**
 * @final
 */
class GoogleAuthenticatorTwoFactorProvider implements TwoFactorProviderInterface
{
    /**
     * @var GoogleAuthenticatorInterface
     */
    private $authenticator;

    /**
     * @var TwoFactorFormRendererInterface
     */
    private $formRenderer;

    public function __construct(GoogleAuthenticatorInterface $authenticator, TwoFactorFormRendererInterface $formRenderer)
    {
        $this->authenticator = $authenticator;
        $this->formRenderer = $formRenderer;
    }

    public function beginAuthentication(AuthenticationContextInterface $context): bool
    {
        // Check if user can do authentication with google authenticator
        $user = $context->getUser();

        return $user instanceof TwoFactorInterface
            && $user->isGoogleAuthenticatorEnabled()
            && $user->getGoogleAuthenticatorSecret();
    }

    public function prepareAuthentication($user): void
    {
    }

    public function validateAuthenticationCode($user, string $authenticationCode): bool
    {
        if (!($user instanceof TwoFactorInterface)) {
            return false;
        }

        return $this->authenticator->checkCode($user, $authenticationCode);
    }

    public function getFormRenderer(): TwoFactorFormRendererInterface
    {
        return $this->formRenderer;
    }
}
