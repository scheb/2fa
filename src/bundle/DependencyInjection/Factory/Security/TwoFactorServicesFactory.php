<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\DependencyInjection\Factory\Security;

use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @final
 *
 * @internal Helper class for TwoFactorFactory only
 */
class TwoFactorServicesFactory
{
    public function createSuccessHandler(ContainerBuilder $container, string $firewallName, array $config, string $twoFactorFirewallConfigId): string
    {
        if (isset($config['success_handler'])) {
            return $config['success_handler'];
        }

        $successHandlerId = TwoFactorFactory::SUCCESS_HANDLER_ID_PREFIX.$firewallName;
        $container
            ->setDefinition($successHandlerId, new ChildDefinition(TwoFactorFactory::SUCCESS_HANDLER_DEFINITION_ID))
            ->replaceArgument(1, new Reference($twoFactorFirewallConfigId));

        return $successHandlerId;
    }

    public function createFailureHandler(ContainerBuilder $container, string $firewallName, array $config, string $twoFactorFirewallConfigId): string
    {
        if (isset($config['failure_handler'])) {
            return $config['failure_handler'];
        }

        $failureHandlerId = TwoFactorFactory::FAILURE_HANDLER_ID_PREFIX.$firewallName;
        $container
            ->setDefinition($failureHandlerId, new ChildDefinition(TwoFactorFactory::FAILURE_HANDLER_DEFINITION_ID))
            ->replaceArgument(1, new Reference($twoFactorFirewallConfigId));

        return $failureHandlerId;
    }

    public function createAuthenticationRequiredHandler(ContainerBuilder $container, string $firewallName, array $config, string $twoFactorFirewallConfigId): string
    {
        if (isset($config['authentication_required_handler'])) {
            return $config['authentication_required_handler'];
        }

        $authenticationRequiredHandlerId = TwoFactorFactory::AUTHENTICATION_REQUIRED_HANDLER_ID_PREFIX.$firewallName;
        $container
            ->setDefinition($authenticationRequiredHandlerId, new ChildDefinition(TwoFactorFactory::AUTHENTICATION_REQUIRED_HANDLER_DEFINITION_ID))
            ->replaceArgument(1, new Reference($twoFactorFirewallConfigId));

        return $authenticationRequiredHandlerId;
    }

    public function getCsrfTokenManagerId(array $config): string
    {
        return $config['enable_csrf'] ?? false
            ? 'scheb_two_factor.csrf_token_manager'
            : 'scheb_two_factor.null_csrf_token_manager';
    }

    public function createTwoFactorFirewallConfig(ContainerBuilder $container, string $firewallName, array $config): string
    {
        $firewallConfigId = TwoFactorFactory::FIREWALL_CONFIG_ID_PREFIX.$firewallName;
        $container
            ->setDefinition($firewallConfigId, new ChildDefinition(TwoFactorFactory::FIREWALL_CONFIG_DEFINITION_ID))
            ->replaceArgument(0, $config)
            ->replaceArgument(1, $firewallName)
            // The SecurityFactory doesn't have access to the service definitions of the bundle. Therefore we tag the
            // definition so we can find it in a compiler pass and add to the the TwoFactorFirewallContext service.
            ->addTag('scheb_two_factor.firewall_config', ['firewall' => $firewallName]);

        return $firewallConfigId;
    }

    public function createProviderPreparationListener(ContainerBuilder $container, string $firewallName, array $config): void
    {
        $firewallConfigId = TwoFactorFactory::PROVIDER_PREPARATION_LISTENER_ID_PREFIX.$firewallName;
        $container
            ->setDefinition($firewallConfigId, new ChildDefinition(TwoFactorFactory::PROVIDER_PREPARATION_LISTENER_DEFINITION_ID))
            ->replaceArgument(3, $firewallName)
            ->replaceArgument(4, $config['prepare_on_login'] ?? TwoFactorFactory::DEFAULT_PREPARE_ON_LOGIN)
            ->replaceArgument(5, $config['prepare_on_access_denied'] ?? TwoFactorFactory::DEFAULT_PREPARE_ON_ACCESS_DENIED)
            ->addTag('kernel.event_subscriber');
    }

    public function createKernelExceptionListener(ContainerBuilder $container, string $firewallName, string $authRequiredHandlerId): void
    {
        $firewallConfigId = TwoFactorFactory::KERNEL_EXCEPTION_LISTENER_ID_PREFIX.$firewallName;
        $container
            ->setDefinition($firewallConfigId, new ChildDefinition(TwoFactorFactory::KERNEL_EXCEPTION_LISTENER_DEFINITION_ID))
            ->replaceArgument(0, $firewallName)
            ->replaceArgument(2, new Reference($authRequiredHandlerId))
            ->addTag('kernel.event_subscriber');
    }

    public function createAccessListener(ContainerBuilder $container, string $firewallName, string $twoFactorFirewallConfigId): void
    {
        $firewallConfigId = TwoFactorFactory::KERNEL_ACCESS_LISTENER_ID_PREFIX.$firewallName;
        $container
            ->setDefinition($firewallConfigId, new ChildDefinition(TwoFactorFactory::KERNEL_ACCESS_LISTENER_DEFINITION_ID))
            ->replaceArgument(0, new Reference($twoFactorFirewallConfigId))
            // The SecurityFactory doesn't have access to the service definitions from the security bundle. Therefore we
            // tag the definition so we can find it in a compiler pass inject it into the firewall context.
            // Compatibility for Symfony <= 5.1
            ->addTag('scheb_two_factor.access_listener', ['firewall' => $firewallName]);
    }

    public function createFormListener(ContainerBuilder $container, string $firewallName, string $twoFactorFirewallConfigId): void
    {
        $firewallConfigId = TwoFactorFactory::FORM_LISTENER_ID_PREFIX.$firewallName;
        $container
            ->setDefinition($firewallConfigId, new ChildDefinition(TwoFactorFactory::FORM_LISTENER_DEFINITION_ID))
            ->replaceArgument(0, new Reference($twoFactorFirewallConfigId))
            ->addTag('kernel.event_subscriber');
    }
}
