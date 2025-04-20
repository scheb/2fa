<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor;

use Scheb\TwoFactorBundle\DependencyInjection\Factory\Security\TwoFactorFactory;
use Scheb\TwoFactorBundle\Security\Http\Utils\RequestDataReader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\HttpUtils;

/**
 * @final
 */
class TwoFactorFirewallConfig
{
    /**
     * @param array<string,mixed> $options
     */
    public function __construct(
        private readonly array $options,
        private readonly string $firewallName,
        private readonly HttpUtils $httpUtils,
        private readonly RequestDataReader $requestDataReader,
    ) {
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

    public function isRememberMeSetsTrusted(): bool
    {
        return $this->options['remember_me_sets_trusted'] ?? TwoFactorFactory::DEFAULT_REMEMBER_ME_SETS_TRUSTED;
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

    public function getCsrfHeader(): string|null
    {
        return $this->options['csrf_header'] ?? null;
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
        return (!$this->isPostOnly() || $request->isMethod('POST'))
            && $this->httpUtils->checkRequestPath($request, $this->getCheckPath());
    }

    public function isAuthFormRequest(Request $request): bool
    {
        return $this->httpUtils->checkRequestPath($request, $this->getAuthFormPath());
    }

    public function getAuthCodeFromRequest(Request $request): string
    {
        return (string) ($this->requestDataReader->getRequestValue($request, $this->getAuthCodeParameterName()) ?? '');
    }

    public function hasTrustedDeviceParameterInRequest(Request $request): bool
    {
        return (bool) $this->requestDataReader->getRequestValue($request, $this->getTrustedParameterName());
    }

    public function getCsrfTokenFromRequest(Request $request): string
    {
        $csrfHeaderName = $this->getCsrfHeader();
        if (null !== $csrfHeaderName && $request->headers->has($csrfHeaderName)) {
            return (string) $request->headers->get($csrfHeaderName);
        }

        return (string) ($this->requestDataReader->getRequestValue($request, $this->getCsrfParameterName()) ?? '');
    }
}
