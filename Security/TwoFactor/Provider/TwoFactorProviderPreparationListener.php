<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Provider;

use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorToken;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvent;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

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

    public function onTwoFactorAuthenticationRequest(TwoFactorAuthenticationEvent $event): void
    {
        $this->twoFactorToken = $event->getToken();
    }

    public function onKernelResponse(): void
    {
        if (!$this->twoFactorToken instanceof TwoFactorToken) {
            return;
        }

        $user = $this->twoFactorToken->getUser();
        $providerName = $this->twoFactorToken->getCurrentTwoFactorProvider();
        $firewallName = $this->twoFactorToken->getProviderKey();

        $calledProviders = $this->session->get(self::CALLED_PROVIDERS_SESSION_KEY, []);
        $firewallCalledProviders = $calledProviders[$firewallName] ?? [];

        if (in_array($providerName, $firewallCalledProviders, true)) {
            return;
        }

        if (!isset($calledProviders[$firewallName])) {
            $calledProviders[$firewallName] = [];
        }
        $calledProviders[$firewallName][] = $providerName;

        $this->providerRegistry->getProvider($providerName)->prepareAuthentication($user);
        $this->session->set(self::CALLED_PROVIDERS_SESSION_KEY, $calledProviders);
    }
}
