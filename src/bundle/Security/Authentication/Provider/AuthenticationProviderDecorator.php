<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Authentication\Provider;

use Scheb\TwoFactorBundle\DependencyInjection\Factory\Security\TwoFactorFactory;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextFactoryInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Handler\AuthenticationHandlerInterface;
use Symfony\Bundle\SecurityBundle\Security\FirewallConfig;
use Symfony\Bundle\SecurityBundle\Security\FirewallMap;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @final
 */
class AuthenticationProviderDecorator implements AuthenticationProviderInterface
{
    /**
     * @var AuthenticationProviderInterface
     */
    private $decoratedAuthenticationProvider;

    /**
     * @var AuthenticationHandlerInterface
     */
    private $twoFactorAuthenticationHandler;

    /**
     * @var AuthenticationContextFactoryInterface
     */
    private $authenticationContextFactory;

    /**
     * @var FirewallMap
     */
    private $firewallMap;

    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(
        AuthenticationProviderInterface $decoratedAuthenticationProvider,
        AuthenticationHandlerInterface $twoFactorAuthenticationHandler,
        AuthenticationContextFactoryInterface $authenticationContextFactory,
        FirewallMap $firewallMap,
        RequestStack $requestStack
    ) {
        $this->decoratedAuthenticationProvider = $decoratedAuthenticationProvider;
        $this->twoFactorAuthenticationHandler = $twoFactorAuthenticationHandler;
        $this->authenticationContextFactory = $authenticationContextFactory;
        $this->firewallMap = $firewallMap;
        $this->requestStack = $requestStack;
    }

    public function supports(TokenInterface $token): bool
    {
        return $this->decoratedAuthenticationProvider->supports($token);
    }

    public function authenticate(TokenInterface $token): TokenInterface
    {
        $wasAlreadyAuthenticated = $token->isAuthenticated();
        /** @psalm-suppress InternalMethod */
        $token = $this->decoratedAuthenticationProvider->authenticate($token);

        // Only trigger two-factor authentication when the provider was called with an unauthenticated token. When we
        // get an authenticated token passed, we're not doing a login, but the system refreshes the token. Then we don't
        // want to start two-factor authentication or we're ending in an endless loop.
        if ($wasAlreadyAuthenticated) {
            return $token;
        }

        // AnonymousToken and TwoFactorTokenInterface can be ignored
        if ($token instanceof AnonymousToken || $token instanceof TwoFactorTokenInterface) {
            return $token;
        }

        $request = $this->getRequest();
        $firewallConfig = $this->getFirewallConfig($request);

        if (!\in_array(TwoFactorFactory::AUTHENTICATION_PROVIDER_KEY, $firewallConfig->getListeners(), true)) {
            return $token; // This firewall doesn't support two-factor authentication
        }

        $context = $this->authenticationContextFactory->create($request, $token, $firewallConfig->getName());

        return $this->twoFactorAuthenticationHandler->beginTwoFactorAuthentication($context);
    }

    /**
     * @return mixed
     */
    public function __call(string $method, array $arguments)
    {
        return ($this->decoratedAuthenticationProvider)->{$method}(...$arguments);
    }

    private function getRequest(): Request
    {
        // Compatibility for Symfony >= 5.3
        if (method_exists(RequestStack::class, 'getMainRequest')) {
            $request = $this->requestStack->getMainRequest();
        } else {
            $request = $this->requestStack->getMasterRequest();
        }
        if (null === $request) {
            throw new \RuntimeException('No request available');
        }

        return $request;
    }

    private function getFirewallConfig(Request $request): FirewallConfig
    {
        $firewallConfig = $this->firewallMap->getFirewallConfig($request);
        if (null === $firewallConfig) {
            throw new \RuntimeException('No firewall configuration available');
        }

        return $firewallConfig;
    }
}
