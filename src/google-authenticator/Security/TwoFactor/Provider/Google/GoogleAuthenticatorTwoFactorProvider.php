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
    public function __construct(private GoogleAuthenticatorInterface $authenticator, private TwoFactorFormRendererInterface $formRenderer)
    {
    }

    public function beginAuthentication(AuthenticationContextInterface $context): bool
    {
        // Check if user can do authentication with google authenticator
        $user = $context->getUser();

        return $user instanceof TwoFactorInterface
            && $user->isGoogleAuthenticatorEnabled()
            && $user->getGoogleAuthenticatorSecret();
    }

    public function prepareAuthentication(mixed $user): void
    {
    }

    public function validateAuthenticationCode(mixed $user, string $authenticationCode): bool
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
