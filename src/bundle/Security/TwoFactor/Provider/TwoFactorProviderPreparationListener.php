<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Provider;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvent;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\AuthenticationEvents;
use Symfony\Component\Security\Core\Event\AuthenticationEvent;

/**
 * @final
 */
class TwoFactorProviderPreparationListener implements EventSubscriberInterface
{
    public const LISTENER_PRIORITY = PHP_INT_MAX;

    /**
     * @var TwoFactorProviderRegistry
     */
    private $providerRegistry;

    /**
     * @var PreparationRecorderInterface
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
        PreparationRecorderInterface $preparationRecorder,
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
            /** @var TwoFactorTokenInterface $token */
            // After login, when the token is a TwoFactorTokenInterface, execute preparation
            $this->twoFactorToken = $token;
        }
    }

    public function onAccessDenied(TwoFactorAuthenticationEvent $event): void
    {
        $token = $event->getToken();
        if ($this->prepareOnAccessDenied && $this->supports($token)) {
            /** @var TwoFactorTokenInterface $token */
            // Whenever two-factor authentication is required, execute preparation
            $this->twoFactorToken = $token;
        }
    }

    public function onTwoFactorForm(TwoFactorAuthenticationEvent $event): void
    {
        $token = $event->getToken();
        if ($this->supports($token)) {
            /** @var TwoFactorTokenInterface $token */
            // Whenever two-factor authentication form is shown, execute preparation
            $this->twoFactorToken = $token;
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMasterRequest()) {
            return;
        }
        if (!($this->twoFactorToken instanceof TwoFactorTokenInterface)) {
            return;
        }

        $providerName = $this->twoFactorToken->getCurrentTwoFactorProvider();
        if (null === $providerName) {
            return;
        }

        $firewallName = $this->twoFactorToken->getProviderKey();

        if ($this->preparationRecorder->isTwoFactorProviderPrepared($firewallName, $providerName)) {
            $this->logger->info(sprintf('Two-factor provider "%s" was already prepared.', $providerName));

            return;
        }

        $user = $this->twoFactorToken->getUser();
        $this->providerRegistry->getProvider($providerName)->prepareAuthentication($user);
        $this->preparationRecorder->setTwoFactorProviderPrepared($firewallName, $providerName);
        $this->logger->info(sprintf('Two-factor provider "%s" prepared.', $providerName));
    }

    private function supports(TokenInterface $token): bool
    {
        return $token instanceof TwoFactorTokenInterface && $token->getProviderKey() === $this->firewallName;
    }

    public static function getSubscribedEvents()
    {
        return [
            // This must trigger very first, followed by AuthenticationSuccessEventSuppressor
            AuthenticationEvents::AUTHENTICATION_SUCCESS => ['onLogin', self::LISTENER_PRIORITY],
            TwoFactorAuthenticationEvents::REQUIRE => 'onAccessDenied',
            TwoFactorAuthenticationEvents::FORM => 'onTwoFactorForm',
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }
}
