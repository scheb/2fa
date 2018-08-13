<?php

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Provider;

use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorToken;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvent;

class TwoFactorProviderPreparationListener
{
    /**
     * @var TwoFactorProviderRegistry
     */
    private $providerRegistry;

    public function __construct(TwoFactorProviderRegistry $providerRegistry)
    {
        $this->providerRegistry = $providerRegistry;
    }

    public function onTwoFactorAuthenticationRequest(TwoFactorAuthenticationEvent $event)
    {
        /** @var TwoFactorToken $token */
        $token = $event->getToken();
        $user = $token->getUser();
        $providerName = $token->getCurrentTwoFactorProvider();
        $this->providerRegistry->getProvider($providerName)->prepareAuthentication($user);
    }
}
