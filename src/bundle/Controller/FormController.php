<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Controller;

use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Exception\UnknownTwoFactorProviderException;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderRegistry;
use Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\TrustedDeviceManagerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\TwoFactorFirewallConfig;
use Scheb\TwoFactorBundle\Security\TwoFactor\TwoFactorFirewallContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Logout\LogoutUrlGenerator;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use function count;
use function str_contains;

/**
 * @api Part of the bundle's public API, may be extended
 */
class FormController
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly TwoFactorProviderRegistry $providerRegistry,
        private readonly TwoFactorFirewallContext $twoFactorFirewallContext,
        private readonly LogoutUrlGenerator $logoutUrlGenerator,
        private readonly TrustedDeviceManagerInterface|null $trustedDeviceManager,
        private readonly bool $trustedFeatureEnabled,
    ) {
    }

    public function form(Request $request): Response
    {
        $token = $this->getTwoFactorToken();
        $this->setPreferredProvider($request, $token);

        $providerName = $token->getCurrentTwoFactorProvider();
        if (null === $providerName) {
            throw new AccessDeniedException('User is not in a two-factor authentication process.');
        }

        return $this->renderForm($providerName, $request, $token);
    }

    protected function getTwoFactorToken(): TwoFactorTokenInterface
    {
        $token = $this->tokenStorage->getToken();
        if (!($token instanceof TwoFactorTokenInterface)) {
            throw new AccessDeniedException('User is not in a two-factor authentication process.');
        }

        return $token;
    }

    protected function setPreferredProvider(Request $request, TwoFactorTokenInterface $token): void
    {
        $preferredProvider = (string) $request->query->get('preferProvider');
        if (!$preferredProvider) {
            return;
        }

        try {
            $token->preferTwoFactorProvider($preferredProvider);
        } catch (UnknownTwoFactorProviderException) {
            // Bad user input
        }
    }

    /**
     * @return array<string,mixed>
     */
    protected function getTemplateVars(Request $request, TwoFactorTokenInterface $token): array
    {
        $config = $this->twoFactorFirewallContext->getFirewallConfig($token->getFirewallName());
        $pendingTwoFactorProviders = $token->getTwoFactorProviders();
        $displayTrustedOption = $this->canSetTrustedDevice($token, $request, $config);
        $authenticationException = $this->getLastAuthenticationException($request->getSession());
        $checkPath = $config->getCheckPath();
        $isRoute = !str_contains($checkPath, '/');

        return [
            'twoFactorProvider' => $token->getCurrentTwoFactorProvider(),
            'availableTwoFactorProviders' => $pendingTwoFactorProviders,
            'authenticationError' => $authenticationException?->getMessageKey(),
            'authenticationErrorData' => $authenticationException?->getMessageData(),
            'displayTrustedOption' => $displayTrustedOption,
            'authCodeParameterName' => $config->getAuthCodeParameterName(),
            'trustedParameterName' => $config->getTrustedParameterName(),
            'isCsrfProtectionEnabled' => $config->isCsrfProtectionEnabled(),
            'csrfParameterName' => $config->getCsrfParameterName(),
            'csrfTokenId' => $config->getCsrfTokenId(),
            'checkPathRoute' => $isRoute ? $checkPath : null,
            'checkPathUrl' => $isRoute ? null : $checkPath,
            'logoutPath' => $this->logoutUrlGenerator->getLogoutPath(),
        ];
    }

    protected function renderForm(string $providerName, Request $request, TwoFactorTokenInterface $token): Response
    {
        $renderer = $this->providerRegistry->getProvider($providerName)->getFormRenderer();
        $templateVars = $this->getTemplateVars($request, $token);

        return $renderer->renderForm($request, $templateVars);
    }

    protected function getLastAuthenticationException(SessionInterface $session): AuthenticationException|null
    {
        $authException = $session->get(SecurityRequestAttributes::AUTHENTICATION_ERROR);
        if ($authException instanceof AuthenticationException) {
            $session->remove(SecurityRequestAttributes::AUTHENTICATION_ERROR);

            return $authException;
        }

        return null; // The value does not come from the security component.
    }

    private function canSetTrustedDevice(TwoFactorTokenInterface $token, Request $request, TwoFactorFirewallConfig $config): bool
    {
        return $this->trustedFeatureEnabled
            && $this->trustedDeviceManager
            && $this->trustedDeviceManager->canSetTrustedDevice($token->getUser(), $request, $config->getFirewallName())
            && (!$config->isMultiFactor() || 1 === count($token->getTwoFactorProviders()));
    }
}
