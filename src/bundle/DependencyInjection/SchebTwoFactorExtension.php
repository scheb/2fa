<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use function assert;
use function is_bool;
use function is_string;
use function trim;

/**
 * @final
 */
class SchebTwoFactorExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('scheb_two_factor.model_manager_name', $config['model_manager_name']);
        $container->setParameter('scheb_two_factor.security_tokens', $config['security_tokens']);
        $container->setParameter('scheb_two_factor.ip_whitelist', $config['ip_whitelist']);

        $loader = new Loader\PhpFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('security.php');
        $loader->load('persistence.php');
        $loader->load('two_factor.php');

        // Load two-factor modules
        if (isset($config['email']['enabled']) && $this->resolveFeatureFlag($container, $config['email']['enabled'])) {
            $this->configureEmailAuthenticationProvider($container, $config);
        }

        if (isset($config['google']['enabled']) && $this->resolveFeatureFlag($container, $config['google']['enabled'])) {
            $this->configureGoogleAuthenticationProvider($container, $config);
        }

        if (isset($config['totp']['enabled']) && $this->resolveFeatureFlag($container, $config['totp']['enabled'])) {
            $this->configureTotpAuthenticationProvider($container, $config);
        }

        // Configure custom services
        $this->configurePersister($container, $config);
        $this->configureTwoFactorConditions($container, $config);
        $this->configureIpWhitelistProvider($container, $config);
        $this->configureTokenFactory($container, $config);
        $this->configureProviderDecider($container, $config);
        $this->configureCodeReuseCache($container, $config);

        if (isset($config['trusted_device']['enabled']) && $this->resolveFeatureFlag($container, $config['trusted_device']['enabled'])) {
            $this->configureTrustedDeviceManager($container, $config);
        } else {
            $container->setParameter('scheb_two_factor.trusted_device.enabled', false);
        }

        if (!isset($config['backup_codes']['enabled']) || !$this->resolveFeatureFlag($container, $config['backup_codes']['enabled'])) {
            return;
        }

        $this->configureBackupCodeManager($container, $config);
    }

    /**
     * @param array<string,mixed> $config
     */
    private function configurePersister(ContainerBuilder $container, array $config): void
    {
        $container->setAlias('scheb_two_factor.persister', $config['persister']);
    }

    /**
     * @param array<string,mixed> $config
     */
    private function configureTwoFactorConditions(ContainerBuilder $container, array $config): void
    {
        $conditions = [
            new Reference('scheb_two_factor.authenticated_token_condition'),
            new Reference('scheb_two_factor.ip_whitelist_condition'),
        ];

        // Custom two-factor condition
        if (null !== $config['two_factor_condition']) {
            $conditions[] = new Reference($config['two_factor_condition']);
        }

        $conditionRegistryDefinition = $container->getDefinition('scheb_two_factor.condition_registry');
        $conditionRegistryDefinition->setArgument(0, new IteratorArgument($conditions));
    }

    private function addTwoFactorCondition(ContainerBuilder $container, Reference $serviceReference): void
    {
        $conditionRegistryDefinition = $container->getDefinition('scheb_two_factor.condition_registry');
        $conditionsIterator = $conditionRegistryDefinition->getArgument(0);
        assert($conditionsIterator instanceof IteratorArgument);
        $conditions = $conditionsIterator->getValues();
        $conditions[] = $serviceReference;
        $conditionsIterator->setValues($conditions);
    }

    /**
     * @param array<string,mixed> $config
     */
    private function configureTrustedDeviceManager(ContainerBuilder $container, array $config): void
    {
        $loader = new Loader\PhpFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('trusted_device.php');
        $container->setAlias('scheb_two_factor.trusted_device_manager', $config['trusted_device']['manager']);

        $this->addTwoFactorCondition($container, new Reference('scheb_two_factor.trusted_device_condition'));

        if (null !== $config['trusted_device']['key']) {
            $jwtEncodeKey = $container->getDefinition('scheb_two_factor.trusted_jwt_encoder.configuration.key');
            $jwtEncodeKey->setArgument(0, $config['trusted_device']['key']);
        }

        $container->setParameter('scheb_two_factor.trusted_device.enabled', $this->resolveFeatureFlag($container, $config['trusted_device']['enabled']));
        $container->setParameter('scheb_two_factor.trusted_device.cookie_name', $config['trusted_device']['cookie_name']);
        $container->setParameter('scheb_two_factor.trusted_device.lifetime', $config['trusted_device']['lifetime']);
        $container->setParameter('scheb_two_factor.trusted_device.extend_lifetime', $config['trusted_device']['extend_lifetime']);
        $container->setParameter('scheb_two_factor.trusted_device.cookie_secure', 'auto' === $config['trusted_device']['cookie_secure'] ? null : $config['trusted_device']['cookie_secure']);
        $container->setParameter('scheb_two_factor.trusted_device.cookie_same_site', $config['trusted_device']['cookie_same_site']);
        $container->setParameter('scheb_two_factor.trusted_device.cookie_domain', $config['trusted_device']['cookie_domain']);
        $container->setParameter('scheb_two_factor.trusted_device.cookie_path', $config['trusted_device']['cookie_path']);
    }

    /**
     * @param array<string,mixed> $config
     */
    private function configureBackupCodeManager(ContainerBuilder $container, array $config): void
    {
        $loader = new Loader\PhpFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('backup_codes.php');
        $container->setAlias('scheb_two_factor.backup_code_manager', $config['backup_codes']['manager']);
    }

    /**
     * @param array<string,mixed> $config
     */
    private function configureIpWhitelistProvider(ContainerBuilder $container, array $config): void
    {
        $container->setAlias('scheb_two_factor.ip_whitelist_provider', $config['ip_whitelist_provider']);
    }

    /**
     * @param array<string,mixed> $config
     */
    private function configureTokenFactory(ContainerBuilder $container, array $config): void
    {
        $container->setAlias('scheb_two_factor.token_factory', $config['two_factor_token_factory']);
    }

    /**
     * @param array<string,mixed> $config
     */
    private function configureProviderDecider(ContainerBuilder $container, array $config): void
    {
        $container->setAlias('scheb_two_factor.provider_decider', $config['two_factor_provider_decider']);
    }

    /**
     * @param array<string,mixed> $config
     */
    private function configureEmailAuthenticationProvider(ContainerBuilder $container, array $config): void
    {
        $loader = new Loader\PhpFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('two_factor_provider_email.php');

        $container->setParameter('scheb_two_factor.email.sender_email', $config['email']['sender_email']);
        $container->setParameter('scheb_two_factor.email.sender_name', $config['email']['sender_name']);
        $container->setParameter('scheb_two_factor.email.template', $config['email']['template']);
        $container->setParameter('scheb_two_factor.email.digits', $config['email']['digits']);
        $container->setAlias('scheb_two_factor.security.email.code_generator', $config['email']['code_generator'])->setPublic(true);

        if (null !== $config['email']['mailer']) {
            $container->setAlias('scheb_two_factor.security.email.auth_code_mailer', $config['email']['mailer']);
        }

        if (null === $config['email']['form_renderer']) {
            return;
        }

        $container->setAlias('scheb_two_factor.security.email.form_renderer', $config['email']['form_renderer']);
    }

    /**
     * @param array<string,mixed> $config
     */
    private function configureGoogleAuthenticationProvider(ContainerBuilder $container, array $config): void
    {
        $loader = new Loader\PhpFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('two_factor_provider_google.php');

        $container->setParameter('scheb_two_factor.google.server_name', $config['google']['server_name']);
        $container->setParameter('scheb_two_factor.google.issuer', $config['google']['issuer']);
        $container->setParameter('scheb_two_factor.google.template', $config['google']['template']);
        $container->setParameter('scheb_two_factor.google.digits', $config['google']['digits']);
        $container->setParameter('scheb_two_factor.google.leeway', $config['google']['leeway']);

        if (null === $config['google']['form_renderer']) {
            return;
        }

        $container->setAlias('scheb_two_factor.security.google.form_renderer', $config['google']['form_renderer']);
    }

    /**
     * @param array<string,mixed> $config
     */
    private function configureTotpAuthenticationProvider(ContainerBuilder $container, array $config): void
    {
        $loader = new Loader\PhpFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('two_factor_provider_totp.php');

        $container->setParameter('scheb_two_factor.totp.issuer', $config['totp']['issuer']);
        $container->setParameter('scheb_two_factor.totp.server_name', $config['totp']['server_name']);
        $container->setParameter('scheb_two_factor.totp.parameters', $config['totp']['parameters']);
        $container->setParameter('scheb_two_factor.totp.template', $config['totp']['template']);
        $container->setParameter('scheb_two_factor.totp.leeway', $config['totp']['leeway']);

        if (null === $config['totp']['form_renderer']) {
            return;
        }

        $container->setAlias('scheb_two_factor.security.totp.form_renderer', $config['totp']['form_renderer']);
    }

    /**
     * @param array<string,mixed> $config
     */
    private function configureCodeReuseCache(ContainerBuilder $container, array $config): void
    {
        if (($config['code_reuse_cache'] ?? null) === null) {
            $config['code_reuse_cache'] = '';
        }

        $container->setParameter('scheb_two_factor.code_reuse_cache_duration', $config['code_reuse_cache_duration']);
        $container->setAlias('scheb_two_factor.code_reuse_cache', $config['code_reuse_cache']);

        if (($config['code_reuse_default_handler'] ?? null) === null) {
            return;
        }

        // phpcs:ignore SlevomatCodingStandard.ControlStructures.EarlyExit.EarlyExitNotUsed
        if ('scheb_two_factor.security.listener.throw_exception_on_two_factor_code_reuse' !== $config['code_reuse_default_handler']) {
            $container->removeDefinition('scheb_two_factor.security.listener.throw_exception_on_two_factor_code_reuse');
        }
    }

    private function resolveFeatureFlag(ContainerBuilder $container, bool|string $value): bool
    {
        $retValue = $container->resolveEnvPlaceholders($value, true);

        if (is_bool($retValue)) {
            return $retValue;
        }

        if (is_string($retValue)) {
            $retValue = trim($retValue);

            if ('false' === $retValue || 'off' === $retValue) {
                return false;
            }

            if ('true' === $retValue || 'on' === $retValue) {
                return true;
            }
        }

        return (bool) $retValue;
    }
}
