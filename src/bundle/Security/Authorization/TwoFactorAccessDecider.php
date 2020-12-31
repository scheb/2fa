<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Authorization;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Http\AccessMapInterface;
use Symfony\Component\Security\Http\Firewall\AccessListener;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\Logout\LogoutUrlGenerator;

/**
 * @final
 */
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

    public function isPubliclyAccessible(Request $request): bool
    {
        list($attributes) = $this->accessMap->getPatterns($request);

        return $this->isPubliclyAccessAttribute($attributes);
    }

    public function isAccessible(Request $request, TokenInterface $token): bool
    {
        list($attributes) = $this->accessMap->getPatterns($request);
        if ($this->isPubliclyAccessAttribute($attributes)) {
            return true;
        }

        // Let routes pass, e.g. if a route needs to be callable during two-factor authentication
        // Compatibility for Symfony < 6.0, true flag to support multiple attributes
        /** @psalm-suppress TooManyArguments */
        if (null !== $attributes && $this->accessDecisionManager->decide($token, $attributes, $request, true)) {
            return true;
        }

        // Compatibility for Symfony <= 5.1
        // From Symfony 5.2 on, the bundle's TwoFactorAccessListener is injected after the LogoutListener, so letting
        // the logout route pass is no longer necessary.
        $logoutPath = $this->removeQueryParameters(
            $this->makeRelativeToBaseUrl($this->logoutUrlGenerator->getLogoutPath(), $request)
        );
        if ($this->httpUtils->checkRequestPath($request, $logoutPath)) {
            return true; // Let the logout route pass
        }

        return false;
    }

    private function isPubliclyAccessAttribute(?array $attributes): bool
    {
        if (null === $attributes) {
            // No access control at all is treated "non-public" by 2fa
            return false;
        }

        if ([AuthenticatedVoter::IS_AUTHENTICATED_ANONYMOUSLY] === $attributes) {
            return true;
        }

        // Compatibility for Symfony 5.1
        if (\defined(AccessListener::class.'::PUBLIC_ACCESS') && [AccessListener::PUBLIC_ACCESS] === $attributes) {
            return true;
        }

        // Compatibility for Symfony 5.2+
        if (\defined(AuthenticatedVoter::class.'::PUBLIC_ACCESS') && [AuthenticatedVoter::PUBLIC_ACCESS] === $attributes) {
            return true;
        }

        return false;
    }

    private function makeRelativeToBaseUrl(string $logoutPath, Request $request): string
    {
        $baseUrl = $request->getBaseUrl();
        if (0 === \strlen($baseUrl)) {
            return $logoutPath;
        }

        $pathInfo = substr($logoutPath, \strlen($baseUrl));
        if (false === $pathInfo || '' === $pathInfo) {
            return '/';
        }

        return $pathInfo;
    }

    private function removeQueryParameters(string $path): string
    {
        $queryPos = strpos($path, '?');
        if (false !== $queryPos) {
            $path = substr($path, 0, $queryPos);
        }

        return $path;
    }
}
