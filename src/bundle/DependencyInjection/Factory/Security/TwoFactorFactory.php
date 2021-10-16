<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\DependencyInjection\Factory\Security;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\SecurityFactoryInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Security\Http\RememberMe\RememberMeHandlerInterface;

/**
 * @internal Technically this class is final, but the compatibility layer needs to extend it
 */
class TwoFactorFactory implements SecurityFactoryInterface, FirewallListenerFactoryInterface
{
    public const AUTHENTICATION_PROVIDER_KEY = 'two_factor';

    public const DEFAULT_CHECK_PATH = '/2fa_check';
    public const DEFAULT_POST_ONLY = true;
    public const DEFAULT_AUTH_FORM_PATH = '/2fa';
    public const DEFAULT_ALWAYS_USE_DEFAULT_TARGET_PATH = false;
    public const DEFAULT_TARGET_PATH = '/';
    public const DEFAULT_AUTH_CODE_PARAMETER_NAME = '_auth_code';
    public const DEFAULT_TRUSTED_PARAMETER_NAME = '_trusted';
    public const DEFAULT_REMEMBER_ME_SETS_TRUSTED = false;
    public const DEFAULT_MULTI_FACTOR = false;
    public const DEFAULT_PREPARE_ON_LOGIN = false;
    public const DEFAULT_PREPARE_ON_ACCESS_DENIED = false;
    public const DEFAULT_ENABLE_CSRF = false;
    public const DEFAULT_CSRF_PARAMETER = '_csrf_token';
    public const DEFAULT_CSRF_TOKEN_ID = 'two_factor';

    public const AUTHENTICATOR_ID_PREFIX = 'security.authenticator.two_factor.';
    public const AUTHENTICATION_TOKEN_CREATED_LISTENER_ID_PREFIX = 'security.authentication.token_created_listener.two_factor.';
    public const PROVIDER_ID_PREFIX = 'security.authentication.provider.two_factor.';
    public const LISTENER_ID_PREFIX = 'security.authentication.listener.two_factor.';
    public const SUCCESS_HANDLER_ID_PREFIX = 'security.authentication.success_handler.two_factor.';
    public const FAILURE_HANDLER_ID_PREFIX = 'security.authentication.failure_handler.two_factor.';
    public const AUTHENTICATION_REQUIRED_HANDLER_ID_PREFIX = 'security.authentication.authentication_required_handler.two_factor.';
    public const FIREWALL_CONFIG_ID_PREFIX = 'security.firewall_config.two_factor.';
    public const PROVIDER_PREPARATION_LISTENER_ID_PREFIX = 'security.authentication.provider_preparation_listener.two_factor.';
    public const KERNEL_EXCEPTION_LISTENER_ID_PREFIX = 'security.authentication.kernel_exception_listener.two_factor.';
    public const KERNEL_ACCESS_LISTENER_ID_PREFIX = 'security.authentication.access_listener.two_factor.';
    public const FORM_LISTENER_ID_PREFIX = 'security.authentication.form_listener.two_factor.';

    public const AUTHENTICATOR_DEFINITION_ID = 'scheb_two_factor.security.authenticator';
    public const AUTHENTICATION_TOKEN_CREATED_LISTENER_DEFINITION_ID = 'scheb_two_factor.security.listener.token_created';
    public const PROVIDER_DEFINITION_ID = 'scheb_two_factor.security.authentication.provider';
    public const LISTENER_DEFINITION_ID = 'scheb_two_factor.security.authentication.listener';
    public const SUCCESS_HANDLER_DEFINITION_ID = 'scheb_two_factor.security.authentication.success_handler';
    public const FAILURE_HANDLER_DEFINITION_ID = 'scheb_two_factor.security.authentication.failure_handler';
    public const AUTHENTICATION_REQUIRED_HANDLER_DEFINITION_ID = 'scheb_two_factor.security.authentication.authentication_required_handler';
    public const FIREWALL_CONFIG_DEFINITION_ID = 'scheb_two_factor.security.firewall_config';
    public const PROVIDER_PREPARATION_LISTENER_DEFINITION_ID = 'scheb_two_factor.security.provider_preparation_listener';
    public const KERNEL_EXCEPTION_LISTENER_DEFINITION_ID = 'scheb_two_factor.security.kernel_exception_listener';
    public const KERNEL_ACCESS_LISTENER_DEFINITION_ID = 'scheb_two_factor.security.access_listener';
    public const FORM_LISTENER_DEFINITION_ID = 'scheb_two_factor.security.form_listener';

    /**
     * @var TwoFactorServicesFactory
     */
    private $twoFactorServicesFactory;

    public function __construct(TwoFactorServicesFactory $twoFactorServicesFactory)
    {
        $this->twoFactorServicesFactory = $twoFactorServicesFactory;
    }

    public function addConfiguration(NodeDefinition $builder): void
    {
        /**
         * @var ArrayNodeDefinition $builder
         * @psalm-suppress PossiblyNullReference
         * @psalm-suppress PossiblyUndefinedMethod
         */
        $builder
            ->children()
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
                ->scalarNode('remember_me_sets_trusted')->defaultValue(self::DEFAULT_REMEMBER_ME_SETS_TRUSTED)->end()
                ->booleanNode('multi_factor')->defaultValue(self::DEFAULT_MULTI_FACTOR)->end()
                ->booleanNode('prepare_on_login')->defaultValue(self::DEFAULT_PREPARE_ON_LOGIN)->end()
                ->booleanNode('prepare_on_access_denied')->defaultValue(self::DEFAULT_PREPARE_ON_ACCESS_DENIED)->end()
                ->scalarNode('enable_csrf')->defaultValue(self::DEFAULT_ENABLE_CSRF)->end()
                ->scalarNode('csrf_parameter')->defaultValue(self::DEFAULT_CSRF_PARAMETER)->end()
                ->scalarNode('csrf_token_id')->defaultValue(self::DEFAULT_CSRF_TOKEN_ID)->end()
                // Fake node for SecurityExtension, which requires a provider to be set when multiple user providers are registered
                ->scalarNode('provider')->defaultNull()->end()
            ->end()
        ;
    }

    /**
     * @param string $id
     * @param array $config
     * @param string $userProviderId
     * @param string|null $defaultEntryPointId
     */
    public function create(ContainerBuilder $container, $id, $config, $userProviderId, $defaultEntryPointId): array
    {
        $csrfTokenManagerId = $this->twoFactorServicesFactory->getCsrfTokenManagerId($config);
        $twoFactorFirewallConfigId = $this->twoFactorServicesFactory->createTwoFactorFirewallConfig($container, $id, $config);
        $successHandlerId = $this->twoFactorServicesFactory->createSuccessHandler($container, $id, $config, $twoFactorFirewallConfigId);
        $failureHandlerId = $this->twoFactorServicesFactory->createFailureHandler($container, $id, $config, $twoFactorFirewallConfigId);
        $authRequiredHandlerId = $this->twoFactorServicesFactory->createAuthenticationRequiredHandler($container, $id, $config, $twoFactorFirewallConfigId);
        $this->twoFactorServicesFactory->createKernelExceptionListener($container, $id, $authRequiredHandlerId);
        $this->twoFactorServicesFactory->createAccessListener($container, $id, $twoFactorFirewallConfigId);
        $this->twoFactorServicesFactory->createFormListener($container, $id, $twoFactorFirewallConfigId);
        $this->twoFactorServicesFactory->createProviderPreparationListener($container, $id, $config);

        $providerId = $this->createAuthenticationProvider($container, $id, $twoFactorFirewallConfigId);
        $listenerId = $this->createAuthenticationListener(
            $container,
            $id,
            $twoFactorFirewallConfigId,
            $successHandlerId,
            $failureHandlerId,
            $authRequiredHandlerId,
            $csrfTokenManagerId
        );

        return [$providerId, $listenerId, $defaultEntryPointId];
    }

    public function createAuthenticator(ContainerBuilder $container, string $firewallName, array $config, string $userProviderId): string
    {
        if (!interface_exists(RememberMeHandlerInterface::class)) {
            throw new \LogicException('Using the authenticator security system with scheb/2fa-bundle requires symfony/security version 5.3 or higher. Either disable "enable_authenticator_manager" or upgrade Symfony.');
        }

        $twoFactorFirewallConfigId = $this->twoFactorServicesFactory->createTwoFactorFirewallConfig($container, $firewallName, $config);
        $successHandlerId = $this->twoFactorServicesFactory->createSuccessHandler($container, $firewallName, $config, $twoFactorFirewallConfigId);
        $failureHandlerId = $this->twoFactorServicesFactory->createFailureHandler($container, $firewallName, $config, $twoFactorFirewallConfigId);
        $authRequiredHandlerId = $this->twoFactorServicesFactory->createAuthenticationRequiredHandler($container, $firewallName, $config, $twoFactorFirewallConfigId);
        $this->twoFactorServicesFactory->createKernelExceptionListener($container, $firewallName, $authRequiredHandlerId);
        $this->twoFactorServicesFactory->createAccessListener($container, $firewallName, $twoFactorFirewallConfigId);
        $this->twoFactorServicesFactory->createFormListener($container, $firewallName, $twoFactorFirewallConfigId);
        $this->twoFactorServicesFactory->createProviderPreparationListener($container, $firewallName, $config);
        $this->createAuthenticationTokenCreatedListener($container, $firewallName);

        return $this->createAuthenticatorService(
            $container,
            $firewallName,
            $twoFactorFirewallConfigId,
            $successHandlerId,
            $failureHandlerId,
            $authRequiredHandlerId
        );
    }

    private function createAuthenticatorService(
        ContainerBuilder $container,
        string $firewallName,
        string $twoFactorFirewallConfigId,
        string $successHandlerId,
        string $failureHandlerId,
        string $authRequiredHandlerId
    ): string {
        $authenticatorId = self::AUTHENTICATOR_ID_PREFIX.$firewallName;
        $container
            ->setDefinition($authenticatorId, new ChildDefinition(self::AUTHENTICATOR_DEFINITION_ID))
            ->replaceArgument(0, new Reference($twoFactorFirewallConfigId))
            ->replaceArgument(2, new Reference($successHandlerId))
            ->replaceArgument(3, new Reference($failureHandlerId))
            ->replaceArgument(4, new Reference($authRequiredHandlerId));

        return $authenticatorId;
    }

    private function createAuthenticationTokenCreatedListener(ContainerBuilder $container, string $firewallName): void
    {
        $listenerId = self::AUTHENTICATION_TOKEN_CREATED_LISTENER_ID_PREFIX.$firewallName;
        $container
            ->setDefinition($listenerId, new ChildDefinition(self::AUTHENTICATION_TOKEN_CREATED_LISTENER_DEFINITION_ID))
            ->replaceArgument(0, $firewallName)
            // Important: register event only for the specific firewall
            ->addTag('kernel.event_subscriber', ['dispatcher' => 'security.event_dispatcher.'.$firewallName]);
    }

    private function createAuthenticationProvider(ContainerBuilder $container, string $firewallName, string $twoFactorFirewallConfigId): string
    {
        $providerId = self::PROVIDER_ID_PREFIX.$firewallName;
        $container
            ->setDefinition($providerId, new ChildDefinition(self::PROVIDER_DEFINITION_ID))
            ->replaceArgument(0, new Reference($twoFactorFirewallConfigId));

        return $providerId;
    }

    private function createAuthenticationListener(
        ContainerBuilder $container,
        string $firewallName,
        string $twoFactorFirewallConfigId,
        string $successHandlerId,
        string $failureHandlerId,
        string $authRequiredHandlerId,
        string $csrfTokenManagerId
    ): string {
        $listenerId = self::LISTENER_ID_PREFIX.$firewallName;
        $container
            ->setDefinition($listenerId, new ChildDefinition(self::LISTENER_DEFINITION_ID))
            ->replaceArgument(2, new Reference($twoFactorFirewallConfigId))
            ->replaceArgument(3, new Reference($successHandlerId))
            ->replaceArgument(4, new Reference($failureHandlerId))
            ->replaceArgument(5, new Reference($authRequiredHandlerId))
            ->replaceArgument(6, new Reference($csrfTokenManagerId));

        return $listenerId;
    }

    // Compatibility for Symfony >= 5.2
    // Uses this interface to inject TwoFactorAccessListener, instead of using the compiler pass.
    public function createListeners(ContainerBuilder $container, string $firewallName, array $config): array
    {
        $accessListenerId = TwoFactorFactory::KERNEL_ACCESS_LISTENER_ID_PREFIX.$firewallName;

        return [$accessListenerId];
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
