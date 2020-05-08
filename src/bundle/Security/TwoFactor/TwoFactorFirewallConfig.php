<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor;

use Scheb\TwoFactorBundle\DependencyInjection\Factory\Security\TwoFactorFactory;

class TwoFactorFirewallConfig
{
    /**
     * @var array
     */
    private $options;

    public function __construct(array $options)
    {
        $this->options = $options;
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
        return null !== ($this->options['csrf_token_generator'] ?? null);
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
}
