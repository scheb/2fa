<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor;

use Scheb\TwoFactorBundle\DependencyInjection\Factory\Security\TwoFactorFactory;
use Scheb\TwoFactorBundle\Security\Http\ParameterBagUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\HttpUtils;

class TwoFactorFirewallConfig
{
    /**
     * @var array
     */
    private $options;

    /**
     * @var string
     */
    private $firewallName;

    /**
     * @var HttpUtils
     */
    private $httpUtils;

    public function __construct(
        array $options,
        string $firewallName,
        HttpUtils $httpUtils
    ) {
        $this->options = $options;
        $this->firewallName = $firewallName;
        $this->httpUtils = $httpUtils;
    }

    public function getFirewallName(): string
    {
        return $this->firewallName;
    }

    public function isMultiFactor(): bool
    {
        return $this->options['multi_factor'] ?? TwoFactorFactory::DEFAULT_MULTI_FACTOR;
    }

    public function getAuthCodeParameterName(): string
    {
        return $this->options['auth_code_parameter_name'] ?? TwoFactorFactory::DEFAULT_AUTH_CODE_PARAMETER_NAME;
    }

    public function getTrustedParameterName(): string
    {
        return $this->options['trusted_parameter_name'] ?? TwoFactorFactory::DEFAULT_TRUSTED_PARAMETER_NAME;
    }

    public function isCsrfProtectionEnabled(): bool
    {
        return $this->options['enable_csrf'] ?? TwoFactorFactory::DEFAULT_ENABLE_CSRF;
    }

    public function getCsrfParameterName(): string
    {
        return $this->options['csrf_parameter'] ?? TwoFactorFactory::DEFAULT_CSRF_PARAMETER;
    }

    public function getCsrfTokenId(): string
    {
        return $this->options['csrf_token_id'] ?? TwoFactorFactory::DEFAULT_CSRF_TOKEN_ID;
    }

    public function getAuthFormPath(): string
    {
        return $this->options['auth_form_path'] ?? TwoFactorFactory::DEFAULT_AUTH_FORM_PATH;
    }

    public function getCheckPath(): string
    {
        return $this->options['check_path'] ?? TwoFactorFactory::DEFAULT_CHECK_PATH;
    }

    public function isPostOnly(): bool
    {
        return $this->options['post_only'] ?? TwoFactorFactory::DEFAULT_POST_ONLY;
    }

    public function isAlwaysUseDefaultTargetPath(): bool
    {
        return $this->options['always_use_default_target_path'] ?? TwoFactorFactory::DEFAULT_ALWAYS_USE_DEFAULT_TARGET_PATH;
    }

    public function getDefaultTargetPath(): string
    {
        return $this->options['default_target_path'] ?? TwoFactorFactory::DEFAULT_TARGET_PATH;
    }

    public function isCheckPathRequest(Request $request): bool
    {
        return ($this->isPostOnly() ? $request->isMethod('POST') : true)
            && $this->httpUtils->checkRequestPath($request, $this->getCheckPath());
    }

    public function isAuthFormRequest(Request $request): bool
    {
        return $this->httpUtils->checkRequestPath($request, $this->getAuthFormPath());
    }

    public function getAuthCodeFromRequest(Request $request): string
    {
        return ParameterBagUtils::getRequestParameterValue($request, $this->getAuthCodeParameterName()) ?? '';
    }

    public function hasTrustedDeviceParameterInRequest(Request $request): bool
    {
        return (bool) ParameterBagUtils::getRequestParameterValue($request, $this->getTrustedParameterName());
    }

    public function getCsrfTokenFromRequest(Request $request): string
    {
        return ParameterBagUtils::getRequestParameterValue($request, $this->getCsrfParameterName()) ?? '';
    }
}
