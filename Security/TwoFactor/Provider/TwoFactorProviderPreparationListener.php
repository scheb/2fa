<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Provider;

use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorToken;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvent;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class TwoFactorProviderPreparationListener
{
    private const CALLED_PROVIDERS_SESSION_KEY = '2fa_called_providers';

    /**
     * @var TwoFactorProviderRegistry
     */
    private $providerRegistry;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var TwoFactorToken|null
     */
    private $twoFactorToken;

    public function __construct(TwoFactorProviderRegistry $providerRegistry, SessionInterface $session)
    {
        $this->providerRegistry = $providerRegistry;
        $this->session = $session;
    }

    public function onTwoFactorAuthenticationRequest(TwoFactorAuthenticationEvent $event)
    {
        $this->twoFactorToken = $event->getToken();
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        if (!$this->twoFactorToken instanceof TwoFactorToken) {
            return;
        }

        $user = $this->twoFactorToken->getUser();
        $providerName = $this->twoFactorToken->getCurrentTwoFactorProvider();
        $calledProviders = $this->session->get(self::CALLED_PROVIDERS_SESSION_KEY, []);
        if (in_array($providerName, $calledProviders, true)) {
            return;
        }
        $calledProviders[] = $providerName;
        $this->session->set(self::CALLED_PROVIDERS_SESSION_KEY, $calledProviders);
        $this->providerRegistry->getProvider($providerName)->prepareAuthentication($user);
    }
}
