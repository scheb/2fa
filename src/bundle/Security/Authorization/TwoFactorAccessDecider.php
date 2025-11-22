<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Authorization;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecision;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Http\AccessMapInterface;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\Logout\LogoutUrlGenerator;
use function strlen;
use function strpos;
use function substr;

/**
 * @final
 */
class TwoFactorAccessDecider
{
    public function __construct(
        private readonly AccessMapInterface $accessMap,
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
        private readonly HttpUtils $httpUtils,
        private readonly LogoutUrlGenerator $logoutUrlGenerator,
    ) {
    }

    public function isPubliclyAccessible(Request $request): bool
    {
        [$attributes] = $this->accessMap->getPatterns($request);

        return $this->isPubliclyAccessAttribute($attributes);
    }

    public function isAccessible(Request $request, TokenInterface $token): bool
    {
        [$attributes] = $this->accessMap->getPatterns($request);
        if ($this->isPubliclyAccessAttribute($attributes)) {
            return true;
        }

        // Let routes pass, e.g. if a route needs to be callable during two-factor authentication
        /** @psalm-suppress TooManyArguments */
        if (null !== $attributes && $this->accessDecisionManager->decide($token, $attributes, $request, new AccessDecision(), true)) {
            return true;
        }

        // This block of code ensures requests to the logout route can pass.
        $logoutPath = $this->removeQueryParameters(
            $this->makeRelativeToBaseUrl($this->logoutUrlGenerator->getLogoutPath(), $request),
        );

        return $this->httpUtils->checkRequestPath($request, $logoutPath); // Let the logout route pass
    }

    private function isPubliclyAccessAttribute(array|null $attributes): bool
    {
        if (null === $attributes) {
            // No access control at all is treated "non-public" by 2fa
            return false;
        }

        return [AuthenticatedVoter::PUBLIC_ACCESS] === $attributes;
    }

    private function makeRelativeToBaseUrl(string $logoutPath, Request $request): string
    {
        $baseUrl = $request->getBaseUrl();
        if (0 === strlen($baseUrl)) {
            return $logoutPath;
        }

        $pathInfo = substr($logoutPath, strlen($baseUrl));
        if ('' === $pathInfo) {
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
