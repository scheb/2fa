<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Handler;

use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\IpWhitelist\IpWhitelistProviderInterface;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @final
 */
class IpWhitelistHandler implements AuthenticationHandlerInterface
{
    /**
     * @var AuthenticationHandlerInterface
     */
    private $authenticationHandler;

    /**
     * @var IpWhitelistProviderInterface
     */
    private $ipWhitelistProvider;

    public function __construct(AuthenticationHandlerInterface $authenticationHandler, IpWhitelistProviderInterface $ipWhitelistProvider)
    {
        $this->authenticationHandler = $authenticationHandler;
        $this->ipWhitelistProvider = $ipWhitelistProvider;
    }

    public function beginTwoFactorAuthentication(AuthenticationContextInterface $context): TokenInterface
    {
        $request = $context->getRequest();

        // Skip two-factor authentication for whitelisted IPs
        if (IpUtils::checkIp($request->getClientIp(), $this->ipWhitelistProvider->getWhitelistedIps($context))) {
            return $context->getToken();
        }

        return $this->authenticationHandler->beginTwoFactorAuthentication($context);
    }
}
