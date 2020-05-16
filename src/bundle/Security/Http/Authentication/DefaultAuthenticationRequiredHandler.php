<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Http\Authentication;

use Scheb\TwoFactorBundle\Security\TwoFactor\TwoFactorFirewallConfig;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class DefaultAuthenticationRequiredHandler implements AuthenticationRequiredHandlerInterface
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

    public function onAuthenticationRequired(Request $request, TokenInterface $token): Response
    {
        // Do not save the target path when the current one is the one for checking the authentication code. Then it's
        // another redirect which happens in multi-factor scenarios.
        if (!$this->config->isCheckPathRequest($request) && $request->hasSession() && $request->isMethodSafe() && !$request->isXmlHttpRequest()) {
            $this->saveTargetPath($request->getSession(), $this->config->getFirewallName(), $request->getUri());
        }

        return $this->httpUtils->createRedirectResponse($request, $this->config->getAuthFormPath());
    }
}
