<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Http\Authentication;

use Scheb\TwoFactorBundle\Security\TwoFactor\TwoFactorFirewallConfig;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\HttpUtils;

class DefaultAuthenticationFailureHandler implements AuthenticationFailureHandlerInterface
{
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

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);

        return $this->httpUtils->createRedirectResponse($request, $this->config->getAuthFormPath());
    }
}
