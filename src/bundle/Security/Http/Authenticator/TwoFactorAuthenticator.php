<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Http\Authenticator;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\Http\Authentication\AuthenticationRequiredHandlerInterface;
use Scheb\TwoFactorBundle\Security\Http\Authenticator\Passport\Badge\TrustedDeviceBadge;
use Scheb\TwoFactorBundle\Security\Http\Authenticator\Passport\Credentials\TwoFactorCodeCredentials;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvent;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvents;
use Scheb\TwoFactorBundle\Security\TwoFactor\TwoFactorFirewallConfig;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\InteractiveAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use function assert;
use function class_exists;

/**
 * @final
 */
class TwoFactorAuthenticator implements AuthenticatorInterface, InteractiveAuthenticatorInterface
{
    public const FLAG_2FA_COMPLETE = '2fa_complete';

    private readonly LoggerInterface $logger;

    public function __construct(
        private readonly TwoFactorFirewallConfig $twoFactorFirewallConfig,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly AuthenticationSuccessHandlerInterface $successHandler,
        private readonly AuthenticationFailureHandlerInterface $failureHandler,
        private readonly AuthenticationRequiredHandlerInterface $authenticationRequiredHandler,
        private readonly EventDispatcherInterface $eventDispatcher,
        LoggerInterface|null $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    public function supports(Request $request): bool|null
    {
        return $this->twoFactorFirewallConfig->isCheckPathRequest($request);
    }

    public function authenticate(Request $request): Passport
    {
        // When the firewall is lazy, the token is not initialized in the "supports" stage, so this check does only work
        // within the "authenticate" stage.
        $currentToken = $this->tokenStorage->getToken();
        if (!($currentToken instanceof TwoFactorTokenInterface)) {
            // This should only happen when the check path is called outside of a 2fa process
            // access_control can't handle this, as it's called after the authenticator
            throw new AccessDeniedException('User is not in a two-factor authentication process.');
        }

        $this->dispatchTwoFactorAuthenticationEvent(TwoFactorAuthenticationEvents::ATTEMPT, $request, $currentToken);

        $credentials = new TwoFactorCodeCredentials($currentToken, $this->twoFactorFirewallConfig->getAuthCodeFromRequest($request));
        $userLoader = (static fn (): UserInterface => $currentToken->getUser());
        $userBadge = new UserBadge($currentToken->getUserIdentifier(), $userLoader);
        $passport = new Passport($userBadge, $credentials, []);
        if ($currentToken->hasAttribute(TwoFactorTokenInterface::ATTRIBUTE_NAME_USE_REMEMBER_ME)) {
            $rememberMeBadge = new RememberMeBadge();
            $rememberMeBadge->enable();
            $passport->addBadge($rememberMeBadge);
        }

        if ($this->twoFactorFirewallConfig->isCsrfProtectionEnabled()) {
            $tokenValue = $this->twoFactorFirewallConfig->getCsrfTokenFromRequest($request);
            $tokenId = $this->twoFactorFirewallConfig->getCsrfTokenId();
            $passport->addBadge(new CsrfTokenBadge($tokenId, $tokenValue));
        }

        // Make sure the trusted device package is installed
        if (class_exists(TrustedDeviceBadge::class) && $this->shouldSetTrustedDevice($request, $passport)) {
            $passport->addBadge(new TrustedDeviceBadge());
        }

        return $passport;
    }

    private function shouldSetTrustedDevice(Request $request, Passport $passport): bool
    {
        return $this->twoFactorFirewallConfig->hasTrustedDeviceParameterInRequest($request)
            || (
                $this->twoFactorFirewallConfig->isRememberMeSetsTrusted()
                && $passport->hasBadge(RememberMeBadge::class)
            );
    }

    public function createToken(Passport $passport, string $firewallName): TokenInterface
    {
        $credentialsBadge = $passport->getBadge(TwoFactorCodeCredentials::class);
        assert($credentialsBadge instanceof TwoFactorCodeCredentials);
        $twoFactorToken = $credentialsBadge->getTwoFactorToken();

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

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): Response|null
    {
        $this->logger->info('User has been two-factor authenticated successfully.', ['username' => $token->getUserIdentifier()]);
        $this->dispatchTwoFactorAuthenticationEvent(TwoFactorAuthenticationEvents::SUCCESS, $request, $token);

        // When it's still a TwoFactorTokenInterface, keep showing the auth form
        if ($token instanceof TwoFactorTokenInterface) {
            $this->dispatchTwoFactorAuthenticationEvent(TwoFactorAuthenticationEvents::REQUIRE, $request, $token);

            return $this->authenticationRequiredHandler->onAuthenticationRequired($request, $token);
        }

        $this->dispatchTwoFactorAuthenticationEvent(TwoFactorAuthenticationEvents::COMPLETE, $request, $token);

        return $this->successHandler->onAuthenticationSuccess($request, $token);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response|null
    {
        $currentToken = $this->tokenStorage->getToken();
        assert($currentToken instanceof TwoFactorTokenInterface);
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
