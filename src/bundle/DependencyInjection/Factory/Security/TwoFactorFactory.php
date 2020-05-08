<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\DependencyInjection\Factory\Security;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\SecurityFactoryInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class TwoFactorFactory implements SecurityFactoryInterface
{
    public const AUTHENTICATION_PROVIDER_KEY = 'two_factor';

    public const DEFAULT_CHECK_PATH = '/2fa_check';
    public const DEFAULT_POST_ONLY = false;
    public const DEFAULT_AUTH_FORM_PATH = '/2fa';
    public const DEFAULT_ALWAYS_USE_DEFAULT_TARGET_PATH = false;
    public const DEFAULT_TARGET_PATH = '/';
    public const DEFAULT_AUTH_CODE_PARAMETER_NAME = '_auth_code';
    public const DEFAULT_TRUSTED_PARAMETER_NAME = '_trusted';
    public const DEFAULT_MULTI_FACTOR = false;
    public const DEFAULT_PREPARE_ON_LOGIN = false;
    public const DEFAULT_PREPARE_ON_ACCESS_DENIED = false;
    public const DEFAULT_CSRF_PARAMETER = '_csrf_token';
    public const DEFAULT_CSRF_TOKEN_ID = 'two_factor';

    public const PROVIDER_ID_PREFIX = 'security.authentication.provider.two_factor.';
    public const LISTENER_ID_PREFIX = 'security.authentication.listener.two_factor.';
    public const SUCCESS_HANDLER_ID_PREFIX = 'security.authentication.success_handler.two_factor.';
    public const FAILURE_HANDLER_ID_PREFIX = 'security.authentication.failure_handler.two_factor.';
    public const AUTHENTICATION_REQUIRED_HANDLER_ID_PREFIX = 'security.authentication.authentication_required_handler.two_factor.';
    public const FIREWALL_CONFIG_ID_PREFIX = 'security.firewall_config.two_factor.';
    public const CSRF_TOKEN_VALIDATOR_ID_PREFIX = 'security.authentication.csrf_token_validator.two_factor.';
    public const PROVIDER_PREPARATION_LISTENER_ID_PREFIX = 'security.authentication.provider_preparation_listener.two_factor.';
    public const AUTHENTICATION_SUCCESS_EVENT_SUPPRESSOR_ID_PREFIX = 'security.authentication.authentication_success_event_suppressor.two_factor.';

    public const PROVIDER_DEFINITION_ID = 'scheb_two_factor.security.authentication.provider';
    public const LISTENER_DEFINITION_ID = 'scheb_two_factor.security.authentication.listener';
    public const SUCCESS_HANDLER_DEFINITION_ID = 'scheb_two_factor.security.authentication.success_handler';
    public const FAILURE_HANDLER_DEFINITION_ID = 'scheb_two_factor.security.authentication.failure_handler';
    public const AUTHENTICATION_REQUIRED_HANDLER_DEFINITION_ID = 'scheb_two_factor.security.authentication.authentication_required_handler';
    public const FIREWALL_CONFIG_DEFINITION_ID = 'scheb_two_factor.security.firewall_config';
    public const CSRF_TOKEN_VALIDATOR_DEFINITION_ID = 'scheb_two_factor.security.authentication.csrf_token_validator';
    public const PROVIDER_PREPARATION_LISTENER_DEFINITION_ID = 'scheb_two_factor.security.provider_preparation_listener';
    public const AUTHENTICATION_SUCCESS_EVENT_SUPPRESSOR_ID = 'scheb_two_factor.security.authentication_success_event_suppressor';

    public function addConfiguration(NodeDefinition $node): void
    {
        /** @var ArrayNodeDefinition $node */
        $builder = $node->children();

        /**
         * @psalm-suppress PossiblyNullReference
         * @psalm-suppress PossiblyUndefinedMethod
         */
        $builder
            ->scalarNode('check_path')->defaultValue(self::DEFAULT_CHECK_PATH)->end()
            ->booleanNode('post_only')->defaultValue(self::DEFAULT_POST_ONLY)->end()
            ->scalarNode('auth_form_path')->defaultValue(self::DEFAULT_AUTH_FORM_PATH)->end()
            ->booleanNode('always_use_default_target_path')->defaultValue(self::DEFAULT_ALWAYS_USE_DEFAULT_TARGET_PATH)->end()
            ->scalarNode('default_target_path')->defaultValue(self::DEFAULT_TARGET_PATH)->end()
            ->scalarNode('success_handler')->defaultNull()->end()
            ->scalarNode('failure_handler')->defaultNull()->end()
            ->scalarNode('authentication_required_handler')->defaultNull()->end()
            ->scalarNode('auth_code_parameter_name')->defaultValue(self::DEFAULT_AUTH_CODE_PARAMETER_NAME)->end()
            ->scalarNode('trusted_parameter_name')->defaultValue(self::DEFAULT_TRUSTED_PARAMETER_NAME)->end()
            ->booleanNode('multi_factor')->defaultValue(self::DEFAULT_MULTI_FACTOR)->end()
            ->booleanNode('prepare_on_login')->defaultValue(self::DEFAULT_PREPARE_ON_LOGIN)->end()
            ->booleanNode('prepare_on_access_denied')->defaultValue(self::DEFAULT_PREPARE_ON_ACCESS_DENIED)->end()
            ->scalarNode('csrf_token_generator')->defaultNull()->end()
            ->scalarNode('csrf_parameter')->defaultValue(self::DEFAULT_CSRF_PARAMETER)->end()
            ->scalarNode('csrf_token_id')->defaultValue(self::DEFAULT_CSRF_TOKEN_ID)->end()
            // Fake node for SecurityExtension, which requires a provider to be set when multiple user providers are registered
            ->scalarNode('provider')->defaultNull()->end()
        ;
    }

    /**
     * @param string $id
     * @param array $config
     * @param string $userProvider
     * @param string|null $defaultEntryPoint
     */
    public function create(ContainerBuilder $container, $id, $config, $userProvider, $defaultEntryPoint): array
    {
        $providerId = $this->createAuthenticationProvider($container, $id, $config);
        $listenerId = $this->createAuthenticationListener($container, $id, $config);
        $this->createProviderPreparationListener($container, $id, $config);
        $this->createAuthenticationSuccessEventSuppressor($container, $id);
        $this->createTwoFactorFirewallConfig($container, $id, $config);

        return [$providerId, $listenerId, $defaultEntryPoint];
    }

    private function createAuthenticationProvider(ContainerBuilder $container, string $firewallName, array $config): string
    {
        $providerId = self::PROVIDER_ID_PREFIX.$firewallName;
        $container
            ->setDefinition($providerId, new ChildDefinition(self::PROVIDER_DEFINITION_ID))
            ->replaceArgument(0, $firewallName)
            ->replaceArgument(1, $config);

        return $providerId;
    }

    private function createAuthenticationListener(ContainerBuilder $container, string $firewallName, array $config): string
    {
        $successHandlerId = $this->createSuccessHandler($container, $firewallName, $config);
        $failureHandlerId = $this->createFailureHandler($container, $firewallName, $config);
        $authRequiredHandlerId = $this->createAuthenticationRequiredHandler($container, $firewallName, $config);
        $csrfTokenValidatorId = $this->createCsrfTokenValidator($container, $firewallName, $config);

        $listenerId = self::LISTENER_ID_PREFIX.$firewallName;
        $container
            ->setDefinition($listenerId, new ChildDefinition(self::LISTENER_DEFINITION_ID))
            ->replaceArgument(3, $firewallName)
            ->replaceArgument(4, new Reference($successHandlerId))
            ->replaceArgument(5, new Reference($failureHandlerId))
            ->replaceArgument(6, new Reference($authRequiredHandlerId))
            ->replaceArgument(7, new Reference($csrfTokenValidatorId))
            ->replaceArgument(8, $config);

        return $listenerId;
    }

    private function createSuccessHandler(ContainerBuilder $container, string $firewallName, array $config): string
    {
        if (isset($config['success_handler'])) {
            return $config['success_handler'];
        }

        $successHandlerId = self::SUCCESS_HANDLER_ID_PREFIX.$firewallName;
        $container
            ->setDefinition($successHandlerId, new ChildDefinition(self::SUCCESS_HANDLER_DEFINITION_ID))
            ->replaceArgument(1, $firewallName)
            ->replaceArgument(2, $config);

        return $successHandlerId;
    }

    private function createFailureHandler(ContainerBuilder $container, string $firewallName, array $config): string
    {
        if (isset($config['failure_handler'])) {
            return $config['failure_handler'];
        }

        $failureHandlerId = self::FAILURE_HANDLER_ID_PREFIX.$firewallName;
        $container
            ->setDefinition($failureHandlerId, new ChildDefinition(self::FAILURE_HANDLER_DEFINITION_ID))
            ->replaceArgument(1, $config);

        return $failureHandlerId;
    }

    private function createAuthenticationRequiredHandler(ContainerBuilder $container, string $firewallName, array $config): string
    {
        if (isset($config['authentication_required_handler'])) {
            return $config['authentication_required_handler'];
        }

        $successHandlerId = self::AUTHENTICATION_REQUIRED_HANDLER_ID_PREFIX.$firewallName;
        $container
            ->setDefinition($successHandlerId, new ChildDefinition(self::AUTHENTICATION_REQUIRED_HANDLER_DEFINITION_ID))
            ->replaceArgument(1, $firewallName)
            ->replaceArgument(2, $config);

        return $successHandlerId;
    }

    private function createCsrfTokenValidator(ContainerBuilder $container, string $firewallName, array $config): string
    {
        $csrfTokenManager = isset($config['csrf_token_generator'])
            ? new Reference($config['csrf_token_generator'])
            : new Reference('scheb_two_factor.null_csrf_token_manager');

        $csrfTokenValidatorId = self::CSRF_TOKEN_VALIDATOR_ID_PREFIX.$firewallName;
        $container
            ->setDefinition($csrfTokenValidatorId, new ChildDefinition(self::CSRF_TOKEN_VALIDATOR_DEFINITION_ID))
            ->replaceArgument(0, $csrfTokenManager)
            ->replaceArgument(1, $config);

        return $csrfTokenValidatorId;
    }

    private function createTwoFactorFirewallConfig(ContainerBuilder $container, string $firewallName, array $config): void
    {
        $firewallConfigId = self::FIREWALL_CONFIG_ID_PREFIX.$firewallName;
        $container
            ->setDefinition($firewallConfigId, new ChildDefinition(self::FIREWALL_CONFIG_DEFINITION_ID))
            ->replaceArgument(0, $config)
            ->addTag('scheb_two_factor.firewall_config', ['firewall' => $firewallName]);

        // The SecurityFactory doesn't have access to the service definitions of the bundle. Therefore we tag the
        // definition so we can find it in a compiler pass and add to the the TwoFactorFirewallContext service.
    }

    private function createProviderPreparationListener(ContainerBuilder $container, string $firewallName, array $config): void
    {
        $firewallConfigId = self::PROVIDER_PREPARATION_LISTENER_ID_PREFIX.$firewallName;
        $container
            ->setDefinition($firewallConfigId, new ChildDefinition(self::PROVIDER_PREPARATION_LISTENER_DEFINITION_ID))
            ->replaceArgument(3, $firewallName)
            ->replaceArgument(4, $config['prepare_on_login'] ?? self::DEFAULT_PREPARE_ON_LOGIN)
            ->replaceArgument(5, $config['prepare_on_access_denied'] ?? self::DEFAULT_PREPARE_ON_ACCESS_DENIED)
            ->addTag('kernel.event_listener', ['event' => 'security.authentication.success', 'method' => 'onLogin', 'priority' => PHP_INT_MAX])
            ->addTag('kernel.event_listener', ['event' => 'scheb_two_factor.authentication.require', 'method' => 'onAccessDenied'])
            ->addTag('kernel.event_listener', ['event' => 'scheb_two_factor.authentication.form', 'method' => 'onTwoFactorForm'])
            ->addTag('kernel.event_listener', ['event' => 'kernel.finish_request', 'method' => 'onKernelFinishRequest']);
    }

    private function createAuthenticationSuccessEventSuppressor(ContainerBuilder $container, string $firewallName): void
    {
        $firewallConfigId = self::AUTHENTICATION_SUCCESS_EVENT_SUPPRESSOR_ID_PREFIX.$firewallName;
        $container
            ->setDefinition($firewallConfigId, new ChildDefinition(self::AUTHENTICATION_SUCCESS_EVENT_SUPPRESSOR_ID))
            ->replaceArgument(0, $firewallName)
            ->addTag('kernel.event_listener', ['event' => 'security.authentication.success', 'method' => 'onLogin', 'priority' => PHP_INT_MAX - 1]);
    }

    public function getPosition(): string
    {
        return 'form';
    }

    public function getKey(): string
    {
        return self::AUTHENTICATION_PROVIDER_KEY;
    }
}
