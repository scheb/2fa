<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Handler;

use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\TrustedDeviceManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @final
 */
class TrustedDeviceHandler implements AuthenticationHandlerInterface
{
    public function __construct(private AuthenticationHandlerInterface $authenticationHandler, private TrustedDeviceManagerInterface $trustedDeviceManager, private bool $extendTrustedToken)
    {
    }

    public function beginTwoFactorAuthentication(AuthenticationContextInterface $context): TokenInterface
    {
        $user = $context->getUser();
        $firewallName = $context->getFirewallName();

        // Skip two-factor authentication on trusted devices
        if ($this->trustedDeviceManager->isTrustedDevice($user, $firewallName)) {
            if (
                $this->extendTrustedToken
                && $this->trustedDeviceManager->canSetTrustedDevice($user, $context->getRequest(), $firewallName)
            ) {
                $this->trustedDeviceManager->addTrustedDevice($user, $firewallName);
            }

            return $context->getToken();
        }

        return $this->authenticationHandler->beginTwoFactorAuthentication($context);
    }
}
