<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Http\Authentication;

use Scheb\TwoFactorBundle\Security\TwoFactor\TwoFactorFirewallConfig;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class DefaultAuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    use TargetPathTrait;

    /**
     * @var HttpUtils
     */
    private $httpUtils;

    /**
     * @var TwoFactorFirewallConfig
     */
    private $config;

    public function __construct(HttpUtils $httpUtils, TwoFactorFirewallConfig $config)
    {
        $this->httpUtils = $httpUtils;
        $this->config = $config;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        $request->getSession()->remove(Security::AUTHENTICATION_ERROR);

        return $this->httpUtils->createRedirectResponse($request, $this->determineRedirectTargetUrl($request));
    }

    private function determineRedirectTargetUrl(Request $request): string
    {
        if ($this->config->isAlwaysUseDefaultTargetPath()) {
            return $this->config->getDefaultTargetPath();
        }

        $session = $request->getSession();
        $firewallName = $this->config->getFirewallName();
        if ($targetUrl = $this->getTargetPath($session, $firewallName)) {
            $this->removeTargetPath($session, $firewallName);

            return $targetUrl;
        }

        return $this->config->getDefaultTargetPath();
    }
}
