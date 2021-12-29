<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google;

use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Exception\TwoFactorProviderLogicException;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorFormRendererInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderInterface;
use function strlen;

/**
 * @final
 */
class GoogleAuthenticatorTwoFactorProvider implements TwoFactorProviderInterface
{
    public function __construct(
        private GoogleAuthenticatorInterface $authenticator,
        private TwoFactorFormRendererInterface $formRenderer,
    ) {
    }

    public function beginAuthentication(AuthenticationContextInterface $context): bool
    {
        $user = $context->getUser();
        if (!($user instanceof TwoFactorInterface && $user->isGoogleAuthenticatorEnabled())) {
            return false;
        }

        // Make sure there is a secret provided when enabled
        $secret = $user->getGoogleAuthenticatorSecret();
        if (null === $secret || 0 === strlen($secret)) {
            throw new TwoFactorProviderLogicException('User has to provide a secret code for Google Authenticator authentication.');
        }

        return true;
    }

    public function prepareAuthentication(object $user): void
    {
    }

    public function validateAuthenticationCode(object $user, string $authenticationCode): bool
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
