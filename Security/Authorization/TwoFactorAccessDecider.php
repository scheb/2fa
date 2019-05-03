<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Authorization;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Http\AccessMapInterface;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\Logout\LogoutUrlGenerator;

class TwoFactorAccessDecider
{
    /**
     * @var AccessMapInterface
     */
    private $accessMap;

    /**
     * @var AccessDecisionManagerInterface
     */
    private $accessDecisionManager;

    /**
     * @var HttpUtils
     */
    private $httpUtils;

    /**
     * @var LogoutUrlGenerator
     */
    private $logoutUrlGenerator;

    public function __construct(
        AccessMapInterface $accessMap,
        AccessDecisionManagerInterface $accessDecisionManager,
        HttpUtils $httpUtils,
        LogoutUrlGenerator $logoutUrlGenerator
    ) {
        $this->accessMap = $accessMap;
        $this->accessDecisionManager = $accessDecisionManager;
        $this->httpUtils = $httpUtils;
        $this->logoutUrlGenerator = $logoutUrlGenerator;
    }

    public function isAccessible(Request $request, TokenInterface $token): bool
    {
        // Let routes pass, e.g. if a route needs to be callable during two-factor authentication
        list($attributes) = $this->accessMap->getPatterns($request);
        if (null !== $attributes && $this->accessDecisionManager->decide($token, $attributes, $request)) {
            return true;
        }

        // Let the logout route pass
        $logoutPath = $this->logoutUrlGenerator->getLogoutPath();
        if ($this->httpUtils->checkRequestPath($request, $logoutPath)) {
            return true;
        }

        return false;
    }
}
