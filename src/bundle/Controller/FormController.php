<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Controller;

use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Exception\UnknownTwoFactorProviderException;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderRegistry;
use Scheb\TwoFactorBundle\Security\TwoFactor\TwoFactorFirewallContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Logout\LogoutUrlGenerator;

class FormController
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var TwoFactorProviderRegistry
     */
    private $providerRegistry;

    /**
     * @var TwoFactorFirewallContext
     */
    private $twoFactorFirewallContext;

    /**
     * @var LogoutUrlGenerator
     */
    private $logoutUrlGenerator;

    /**
     * @var bool
     */
    private $trustedFeatureEnabled;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        TwoFactorProviderRegistry $providerRegistry,
        TwoFactorFirewallContext $twoFactorFirewallContext,
        LogoutUrlGenerator $logoutUrlGenerator,
        bool $trustedFeatureEnabled
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->providerRegistry = $providerRegistry;
        $this->twoFactorFirewallContext = $twoFactorFirewallContext;
        $this->trustedFeatureEnabled = $trustedFeatureEnabled;
        $this->logoutUrlGenerator = $logoutUrlGenerator;
    }

    public function form(Request $request): Response
    {
        $token = $this->getTwoFactorToken();
        $this->setPreferredProvider($request, $token);

        $providerName = $token->getCurrentTwoFactorProvider();
        if (null === $providerName) {
            throw new AccessDeniedException('User is not in a two-factor authentication process.');
        }

        $renderer = $this->providerRegistry->getProvider($providerName)->getFormRenderer();
        $templateVars = $this->getTemplateVars($request, $token);

        return $renderer->renderForm($request, $templateVars);
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
        $preferredProvider = $request->get('preferProvider');
        if ($preferredProvider) {
            try {
                $token->preferTwoFactorProvider($preferredProvider);
            } catch (UnknownTwoFactorProviderException $e) {
                // Bad user input
            }
        }
    }

    protected function getTemplateVars(Request $request, TwoFactorTokenInterface $token): array
    {
        $config = $this->twoFactorFirewallContext->getFirewallConfig($token->getProviderKey());
        $pendingTwoFactorProviders = $token->getTwoFactorProviders();
        $displayTrustedOption = $this->trustedFeatureEnabled && (!$config->isMultiFactor() || 1 === \count($pendingTwoFactorProviders));
        $authenticationException = $this->getLastAuthenticationException($request->getSession());
        $checkPath = $config->getCheckPath();
        $isRoute = false === strpos($checkPath, '/');

        return [
            'twoFactorProvider' => $token->getCurrentTwoFactorProvider(),
            'availableTwoFactorProviders' => $pendingTwoFactorProviders,
            'authenticationError' => $authenticationException ? $authenticationException->getMessageKey() : null,
            'authenticationErrorData' => $authenticationException ? $authenticationException->getMessageData() : null,
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

    protected function getLastAuthenticationException(SessionInterface $session): ?AuthenticationException
    {
        $authException = $session->get(Security::AUTHENTICATION_ERROR);
        if ($authException instanceof AuthenticationException) {
            $session->remove(Security::AUTHENTICATION_ERROR);

            return $authException;
        }

        return null; // The value does not come from the security component.
    }
}
