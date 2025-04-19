<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Http\Authentication;

use Scheb\TwoFactorBundle\Security\TwoFactor\TwoFactorFirewallConfig;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

/**
 * @api Part of the bundle's public API, may be extended
 */
class DefaultAuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    use TargetPathTrait;

    public function __construct(
        private readonly HttpUtils $httpUtils,
        private readonly TwoFactorFirewallConfig $config,
    ) {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        $request->getSession()->remove(SecurityRequestAttributes::AUTHENTICATION_ERROR);

        return $this->httpUtils->createRedirectResponse($request, $this->determineRedirectTargetUrl($request));
    }

    private function determineRedirectTargetUrl(Request $request): string
    {
        if ($this->config->isAlwaysUseDefaultTargetPath()) {
            return $this->config->getDefaultTargetPath();
        }

        $session = $request->getSession();
        $firewallName = $this->config->getFirewallName();
        $targetUrl = $this->getTargetPath($session, $firewallName);
        if (null !== $targetUrl) {
            $this->removeTargetPath($session, $firewallName);

            return $targetUrl;
        }

        return $this->config->getDefaultTargetPath();
    }
}
