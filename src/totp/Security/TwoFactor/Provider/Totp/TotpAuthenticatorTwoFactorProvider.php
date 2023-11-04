<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp;

use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Exception\TwoFactorProviderLogicException;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorFormRendererInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderInterface;
use function strlen;

/**
 * @final
 */
class TotpAuthenticatorTwoFactorProvider implements TwoFactorProviderInterface
{
    public function __construct(
        private readonly TotpAuthenticatorInterface $authenticator,
        private readonly TwoFactorFormRendererInterface $formRenderer,
    ) {
    }

    public function beginAuthentication(AuthenticationContextInterface $context): bool
    {
        $user = $context->getUser();
        if (!($user instanceof TwoFactorInterface && $user->isTotpAuthenticationEnabled())) {
            return false;
        }

        $totpConfiguration = $user->getTotpAuthenticationConfiguration();
        if (null === $totpConfiguration) {
            throw new TwoFactorProviderLogicException('User has to provide a TotpAuthenticationConfiguration for TOTP authentication.');
        }

        $secret = $totpConfiguration->getSecret();
        if (0 === strlen($secret)) {
            throw new TwoFactorProviderLogicException('User has to provide a secret code for TOTP authentication.');
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
