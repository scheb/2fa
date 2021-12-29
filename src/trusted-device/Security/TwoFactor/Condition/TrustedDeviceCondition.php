<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Condition;

use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\TrustedDeviceManagerInterface;

/**
 * @final
 */
class TrustedDeviceCondition implements TwoFactorConditionInterface
{
    public function __construct(
        private TrustedDeviceManagerInterface $trustedDeviceManager,
        private bool $extendTrustedToken,
    ) {
    }

    public function shouldPerformTwoFactorAuthentication(AuthenticationContextInterface $context): bool
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

            return false;
        }

        return true;
    }
}
