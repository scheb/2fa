<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Provider;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvent;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Event\AuthenticationEvent;

class TwoFactorProviderPreparationListener
{
    /**
     * @var TwoFactorProviderRegistry
     */
    private $providerRegistry;

    /**
     * @var TwoFactorProviderPreparationRecorder
     */
    private $preparationRecorder;

    /**
     * @var TwoFactorTokenInterface|null
     */
    private $twoFactorToken;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $firewallName;

    /**
     * @var bool
     */
    private $prepareOnLogin;

    /**
     * @var bool
     */
    private $prepareOnAccessDenied;

    public function __construct(
        TwoFactorProviderRegistry $providerRegistry,
        TwoFactorProviderPreparationRecorder $preparationRecorder,
        ?LoggerInterface $logger,
        string $firewallName,
        bool $prepareOnLogin,
        bool $prepareOnAccessDenied
    ) {
        $this->providerRegistry = $providerRegistry;
        $this->preparationRecorder = $preparationRecorder;
        $this->logger = $logger ?? new NullLogger();
        $this->firewallName = $firewallName;
        $this->prepareOnLogin = $prepareOnLogin;
        $this->prepareOnAccessDenied = $prepareOnAccessDenied;
    }

    public function onLogin(AuthenticationEvent $event): void
    {
        $token = $event->getAuthenticationToken();
        if ($this->prepareOnLogin && $this->supports($token)) {
            // After login, when the token is a TwoFactorTokenInterface, execute preparation
            $this->twoFactorToken = $token;
        }
    }

    public function onAccessDenied(TwoFactorAuthenticationEvent $event): void
    {
        $token = $event->getToken();
        if ($this->prepareOnAccessDenied && $this->supports($token)) {
            // Whenever two-factor authentication is required, execute preparation
            $this->twoFactorToken = $token;
        }
    }

    public function onTwoFactorForm(TwoFactorAuthenticationEvent $event): void
    {
        $token = $event->getToken();
        if ($this->supports($token)) {
            // Whenever two-factor authentication form is shown, execute preparation
            $this->twoFactorToken = $token;
        }
    }

    public function onKernelFinishRequest(FinishRequestEvent $event): void
    {
        if (!$event->isMasterRequest()) {
            return;
        }
        if (!($this->twoFactorToken instanceof TwoFactorTokenInterface)) {
            return;
        }

        $providerName = $this->twoFactorToken->getCurrentTwoFactorProvider();
        $firewallName = $this->twoFactorToken->getProviderKey();

        try {
            if ($this->preparationRecorder->isProviderPrepared($firewallName, $providerName)) {
                $this->logger->info(sprintf('Two-factor provider "%s" was already prepared.', $providerName));

                return;
            }
            $user = $this->twoFactorToken->getUser();
            $this->providerRegistry->getProvider($providerName)->prepareAuthentication($user);
            $this->preparationRecorder->recordProviderIsPrepared($firewallName, $providerName);
            $this->logger->info(sprintf('Two-factor provider "%s" prepared.', $providerName));
        } finally {
            $this->preparationRecorder->saveSession();
        }
    }

    private function supports(TokenInterface $token): bool
    {
        return $token instanceof TwoFactorTokenInterface && $token->getProviderKey() === $this->firewallName;
    }
}
