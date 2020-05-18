<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Http\Firewall;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenFactoryInterface;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\Http\Authentication\AuthenticationRequiredHandlerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvent;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvents;
use Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\TrustedDeviceManagerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\TwoFactorFirewallConfig;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Firewall\AbstractListener;

class TwoFactorListener extends AbstractListener
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var AuthenticationManagerInterface
     */
    private $authenticationManager;

    /**
     * @var TwoFactorFirewallConfig
     */
    private $twoFactorFirewallConfig;

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
     * @var CsrfTokenManagerInterface
     */
    private $csrfTokenManager;

    /**
     * @var TrustedDeviceManagerInterface|null
     */
    private $trustedDeviceManager;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var TwoFactorTokenFactoryInterface
     */
    private $twoFactorTokenFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        AuthenticationManagerInterface $authenticationManager,
        TwoFactorFirewallConfig $twoFactorFirewallConfig,
        AuthenticationSuccessHandlerInterface $successHandler,
        AuthenticationFailureHandlerInterface $failureHandler,
        AuthenticationRequiredHandlerInterface $authenticationRequiredHandler,
        CsrfTokenManagerInterface $csrfTokenManager,
        ?TrustedDeviceManagerInterface $trustedDeviceManager,
        EventDispatcherInterface $eventDispatcher,
        TwoFactorTokenFactoryInterface $twoFactorTokenFactory,
        ?LoggerInterface $logger = null
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->authenticationManager = $authenticationManager;
        $this->twoFactorFirewallConfig = $twoFactorFirewallConfig;
        $this->successHandler = $successHandler;
        $this->failureHandler = $failureHandler;
        $this->authenticationRequiredHandler = $authenticationRequiredHandler;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->trustedDeviceManager = $trustedDeviceManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->twoFactorTokenFactory = $twoFactorTokenFactory;
        $this->logger = $logger ?? new NullLogger();
    }

    public function supports(Request $request): ?bool
    {
        $token = $this->tokenStorage->getToken();

        return $token instanceof TwoFactorTokenInterface
            && $token->getProviderKey() === $this->twoFactorFirewallConfig->getFirewallName()
            && $this->twoFactorFirewallConfig->isCheckPathRequest($request);
    }

    public function authenticate(RequestEvent $event): void
    {
        /** @var TwoFactorTokenInterface $token */
        $token = $this->tokenStorage->getToken();
        $response = $this->attemptAuthentication($event->getRequest(), $token);
        $event->setResponse($response);
    }

    private function attemptAuthentication(Request $request, TwoFactorTokenInterface $beginToken): Response
    {
        $authCode = $this->twoFactorFirewallConfig->getAuthCodeFromRequest($request);
        try {
            if (!$this->hasValidCsrfToken($request)) {
                throw new InvalidCsrfTokenException('Invalid CSRF token.');
            }

            $token = $this->twoFactorTokenFactory->create(
                $beginToken->getAuthenticatedToken(),
                $authCode,
                $beginToken->getProviderKey(),
                $beginToken->getTwoFactorProviders()
            );
            $token->setAttributes($beginToken->getAttributes());

            $this->dispatchTwoFactorAuthenticationEvent(TwoFactorAuthenticationEvents::ATTEMPT, $request, $token);
            $resultToken = $this->authenticationManager->authenticate($token);

            return $this->onSuccess($request, $resultToken, $beginToken);
        } catch (AuthenticationException $failureException) {
            return $this->onFailure($request, $beginToken, $failureException);
        }
    }

    public function hasValidCsrfToken(Request $request): bool
    {
        $tokenValue = $this->twoFactorFirewallConfig->getCsrfTokenFromRequest($request);
        $tokenId = $this->twoFactorFirewallConfig->getCsrfTokenId();
        $token = new CsrfToken($tokenId, $tokenValue);

        return $this->csrfTokenManager->isTokenValid($token);
    }

    private function onFailure(Request $request, TwoFactorTokenInterface $token, AuthenticationException $failureException): Response
    {
        $this->logger->info('Two-factor authentication request failed.', ['exception' => $failureException]);
        $this->dispatchTwoFactorAuthenticationEvent(TwoFactorAuthenticationEvents::FAILURE, $request, $token);

        return $this->failureHandler->onAuthenticationFailure($request, $failureException);
    }

    private function onSuccess(Request $request, TokenInterface $token, TwoFactorTokenInterface $previousTwoFactorToken): Response
    {
        $this->logger->info('User has been two-factor authenticated successfully.', ['username' => $token->getUsername()]);
        $this->tokenStorage->setToken($token);
        $this->dispatchTwoFactorAuthenticationEvent(TwoFactorAuthenticationEvents::SUCCESS, $request, $token);

        // When it's still a TwoFactorTokenInterface, keep showing the auth form
        if ($token instanceof TwoFactorTokenInterface) {
            $this->dispatchTwoFactorAuthenticationEvent(TwoFactorAuthenticationEvents::REQUIRE, $request, $token);

            return $this->authenticationRequiredHandler->onAuthenticationRequired($request, $token);
        }

        $this->dispatchTwoFactorAuthenticationEvent(TwoFactorAuthenticationEvents::COMPLETE, $request, $token);

        $firewallName = $previousTwoFactorToken->getProviderKey();
        if ($this->trustedDeviceManager
            && $this->twoFactorFirewallConfig->hasTrustedDeviceParameterInRequest($request)
            && $this->trustedDeviceManager->canSetTrustedDevice($token->getUser(), $request, $firewallName)
        ) {
            $this->trustedDeviceManager->addTrustedDevice($token->getUser(), $firewallName);
        }

        $response = $this->successHandler->onAuthenticationSuccess($request, $token);
        $this->addRememberMeCookies($previousTwoFactorToken, $response);

        return $response;
    }

    private function dispatchTwoFactorAuthenticationEvent(string $eventType, Request $request, TokenInterface $token): void
    {
        $event = new TwoFactorAuthenticationEvent($request, $token);
        $this->eventDispatcher->dispatch($event, $eventType);
    }

    private function addRememberMeCookies(TwoFactorTokenInterface $twoFactorToken, Response $response): void
    {
        // Add the remember-me cookie that was previously suppressed by two-factor authentication
        if ($twoFactorToken->hasAttribute(TwoFactorTokenInterface::ATTRIBUTE_NAME_REMEMBER_ME_COOKIE)) {
            $rememberMeCookies = $twoFactorToken->getAttribute(TwoFactorTokenInterface::ATTRIBUTE_NAME_REMEMBER_ME_COOKIE);
            foreach ($rememberMeCookies as $cookie) {
                $response->headers->setCookie($cookie);
            }
        }
    }
}
