<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Http\Authenticator;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\Http\Authentication\AuthenticationRequiredHandlerInterface;
use Scheb\TwoFactorBundle\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Scheb\TwoFactorBundle\Security\Http\Authenticator\Passport\Badge\TrustedDeviceBadge;
use Scheb\TwoFactorBundle\Security\Http\Authenticator\Passport\Credentials\TwoFactorCodeCredentials;
use Scheb\TwoFactorBundle\Security\Http\Authenticator\Passport\TwoFactorPassport;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvent;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvents;
use Scheb\TwoFactorBundle\Security\TwoFactor\TwoFactorFirewallConfig;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\InteractiveAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class TwoFactorAuthenticator implements AuthenticatorInterface, InteractiveAuthenticatorInterface
{
    public const FLAG_2FA_COMPLETE = '2fa_complete';

    /**
     * @var TwoFactorFirewallConfig
     */
    private $twoFactorFirewallConfig;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var AuthenticationSuccessHandlerInterface
     */
    private $successHandler;

    /**
     * @var AuthenticationFailureHandlerInterface
     */
    private $failureHandler;

    /**
     * @var AuthenticationRequiredHandlerInterface
     */
    private $authenticationRequiredHandler;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        TwoFactorFirewallConfig $twoFactorFirewallConfig,
        TokenStorageInterface $tokenStorage,
        AuthenticationSuccessHandlerInterface $successHandler,
        AuthenticationFailureHandlerInterface $failureHandler,
        AuthenticationRequiredHandlerInterface $authenticationRequiredHandler,
        EventDispatcherInterface $eventDispatcher,
        ?LoggerInterface $logger = null
    ) {
        $this->twoFactorFirewallConfig = $twoFactorFirewallConfig;
        $this->tokenStorage = $tokenStorage;
        $this->successHandler = $successHandler;
        $this->failureHandler = $failureHandler;
        $this->authenticationRequiredHandler = $authenticationRequiredHandler;
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = $logger ?? new NullLogger();
    }

    public function supports(Request $request): ?bool
    {
        if (!$this->twoFactorFirewallConfig->isCheckPathRequest($request)) {
            return false;
        }

        $currentToken = $this->tokenStorage->getToken();

        return $currentToken instanceof TwoFactorTokenInterface;
    }

    public function authenticate(Request $request): PassportInterface
    {
        /** @var TwoFactorTokenInterface $currentToken */
        $currentToken = $this->tokenStorage->getToken();

        $this->dispatchTwoFactorAuthenticationEvent(TwoFactorAuthenticationEvents::ATTEMPT, $request, $currentToken);

        $credentials = new TwoFactorCodeCredentials($this->twoFactorFirewallConfig->getAuthCodeFromRequest($request));
        $passport = new TwoFactorPassport($currentToken, $credentials, [new RememberMeBadge()]);

        if ($this->twoFactorFirewallConfig->isCsrfProtectionEnabled()) {
            $tokenValue = $this->twoFactorFirewallConfig->getCsrfTokenFromRequest($request);
            $tokenId = $this->twoFactorFirewallConfig->getCsrfTokenId();
            $passport->addBadge(new CsrfTokenBadge($tokenId, $tokenValue));
        }

        if ($this->twoFactorFirewallConfig->hasTrustedDeviceParameterInRequest($request)
            && class_exists(TrustedDeviceBadge::class) // Make sure the package is installed
        ) {
            $passport->addBadge(new TrustedDeviceBadge());
        }

        return $passport;
    }

    public function createAuthenticatedToken(PassportInterface $passport, string $firewallName): TokenInterface
    {
        /** @var TwoFactorPassport $passport */
        $twoFactorToken = $passport->getTwoFactorToken();

        if ($this->isAuthenticationComplete($twoFactorToken)) {
            $authenticatedToken = $twoFactorToken->getAuthenticatedToken(); // Authentication complete, unwrap the token
            $authenticatedToken->setAttribute(self::FLAG_2FA_COMPLETE, true);

            return $authenticatedToken;
        }

        return $twoFactorToken;
    }

    private function isAuthenticationComplete(TwoFactorTokenInterface $token): bool
    {
        return !$this->twoFactorFirewallConfig->isMultiFactor() || $token->allTwoFactorProvidersAuthenticated();
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $this->logger->info('User has been two-factor authenticated successfully.', ['username' => $token->getUsername()]);
        $this->dispatchTwoFactorAuthenticationEvent(TwoFactorAuthenticationEvents::SUCCESS, $request, $token);

        // When it's still a TwoFactorTokenInterface, keep showing the auth form
        if ($token instanceof TwoFactorTokenInterface) {
            $this->dispatchTwoFactorAuthenticationEvent(TwoFactorAuthenticationEvents::REQUIRE, $request, $token);

            return $this->authenticationRequiredHandler->onAuthenticationRequired($request, $token);
        }

        $this->dispatchTwoFactorAuthenticationEvent(TwoFactorAuthenticationEvents::COMPLETE, $request, $token);

        return $this->successHandler->onAuthenticationSuccess($request, $token);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        /** @var TwoFactorTokenInterface $currentToken */
        $currentToken = $this->tokenStorage->getToken();
        $this->logger->info('Two-factor authentication request failed.', ['exception' => $exception]);
        $this->dispatchTwoFactorAuthenticationEvent(TwoFactorAuthenticationEvents::FAILURE, $request, $currentToken);

        return $this->failureHandler->onAuthenticationFailure($request, $exception);
    }

    private function dispatchTwoFactorAuthenticationEvent(string $eventType, Request $request, TokenInterface $token): void
    {
        $event = new TwoFactorAuthenticationEvent($request, $token);
        $this->eventDispatcher->dispatch($event, $eventType);
    }

    public function isInteractive(): bool
    {
        return true;
    }
}
