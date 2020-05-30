<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Http\Authenticator;

use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextFactoryInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Handler\AuthenticationHandlerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\InteractiveAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;

class AuthenticatorDecorator implements InteractiveAuthenticatorInterface
{
    /**
     * @var AuthenticatorInterface
     */
    private $decoratedAuthenticator;

    /**
     * @var AuthenticationHandlerInterface
     */
    private $twoFactorAuthenticationHandler;

    /**
     * @var AuthenticationContextFactoryInterface
     */
    private $authenticationContextFactory;

    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(
        AuthenticatorInterface $decoratedAuthenticator,
        AuthenticationHandlerInterface $twoFactorAuthenticationHandler,
        AuthenticationContextFactoryInterface $authenticationContextFactory,
        RequestStack $requestStack
    ) {
        $this->decoratedAuthenticator = $decoratedAuthenticator;
        $this->twoFactorAuthenticationHandler = $twoFactorAuthenticationHandler;
        $this->authenticationContextFactory = $authenticationContextFactory;
        $this->requestStack = $requestStack;
    }

    public function supports(Request $request): ?bool
    {
        return $this->decoratedAuthenticator->supports($request);
    }

    public function authenticate(Request $request): PassportInterface
    {
        return $this->decoratedAuthenticator->authenticate($request);
    }

    public function createAuthenticatedToken(PassportInterface $passport, string $firewallName): TokenInterface
    {
        $token = $this->decoratedAuthenticator->createAuthenticatedToken($passport, $firewallName);

        // TwoFactorTokenInterface can be ignored
        if ($token instanceof TwoFactorTokenInterface) {
            return $token;
        }

        $request = $this->getRequest();
        $context = $this->authenticationContextFactory->create($request, $token, $firewallName);

        return $this->twoFactorAuthenticationHandler->beginTwoFactorAuthentication($context);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return $this->decoratedAuthenticator->onAuthenticationSuccess($request, $token, $firewallName);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return $this->decoratedAuthenticator->onAuthenticationFailure($request, $exception);
    }

    /**
     * @return mixed
     */
    public function __call(string $method, array $arguments)
    {
        return ($this->decoratedAuthenticator)->{$method}(...$arguments);
    }

    private function getRequest(): Request
    {
        $request = $this->requestStack->getMasterRequest();
        if (null === $request) {
            throw new \RuntimeException('No request available');
        }

        return $request;
    }

    public function isInteractive(): bool
    {
        return $this->decoratedAuthenticator instanceof InteractiveAuthenticatorInterface
            && $this->decoratedAuthenticator->isInteractive();
    }
}
