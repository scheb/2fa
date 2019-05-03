<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Authorization;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Http\AccessMapInterface;

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

    public function __construct(
        AccessMapInterface $accessMap,
        AccessDecisionManagerInterface $accessDecisionManager
    ) {
        $this->accessMap = $accessMap;
        $this->accessDecisionManager = $accessDecisionManager;
    }

    public function isAccessible(Request $request, TokenInterface $token): bool
    {
        // Let routes pass, e.g. if a route needs to be callable during two-factor authentication
        list($attributes) = $this->accessMap->getPatterns($request);
        if (null !== $attributes && $this->accessDecisionManager->decide($token, $attributes, $request)) {
            return true;
        }

        return false;
    }
}
