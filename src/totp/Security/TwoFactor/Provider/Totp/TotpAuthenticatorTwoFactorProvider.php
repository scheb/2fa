<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2020 Christian Scheb
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp;

use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorFormRendererInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderInterface;

class TotpAuthenticatorTwoFactorProvider implements TwoFactorProviderInterface
{
    /**
     * @var TotpAuthenticatorInterface
     */
    private $authenticator;

    /**
     * @var TwoFactorFormRendererInterface
     */
    private $formRenderer;

    public function __construct(TotpAuthenticatorInterface $authenticator, TwoFactorFormRendererInterface $formRenderer)
    {
        $this->authenticator = $authenticator;
        $this->formRenderer = $formRenderer;
    }

    public function beginAuthentication(AuthenticationContextInterface $context): bool
    {
        $user = $context->getUser();

        return $user instanceof TwoFactorInterface
            && $user->isTotpAuthenticationEnabled()
            && $user->getTotpAuthenticationConfiguration();
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
