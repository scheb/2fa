<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Provider;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

class TwoFactorProviderPreparationRecorder
{
    private const CALLED_PROVIDERS_SESSION_KEY = '2fa_called_providers';

    /**
     * @var SessionInterface
     */
    private $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    public function isProviderPrepared(string $firewallName, string $providerName): bool
    {
        $calledProviders = $this->session->get(self::CALLED_PROVIDERS_SESSION_KEY, []);
        $firewallCalledProviders = $calledProviders[$firewallName] ?? [];

        return \in_array($providerName, $firewallCalledProviders, true);
    }

    public function recordProviderIsPrepared(string $firewallName, string $providerName): void
    {
        $calledProviders = $this->session->get(self::CALLED_PROVIDERS_SESSION_KEY, []);
        if (!isset($calledProviders[$firewallName])) {
            $calledProviders[$firewallName] = [];
        }
        $calledProviders[$firewallName][] = $providerName;
        $this->session->set(self::CALLED_PROVIDERS_SESSION_KEY, $calledProviders);
    }

    public function saveSession(): void
    {
        if ($this->session->isStarted()) {
            $this->session->save();
        }
    }
}
