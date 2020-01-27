<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class SchebTwoFactorExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('scheb_two_factor.model_manager_name', $config['model_manager_name']);
        $container->setParameter('scheb_two_factor.security_tokens', $config['security_tokens']);
        $container->setParameter('scheb_two_factor.ip_whitelist', $config['ip_whitelist']);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('security.xml');
        $loader->load('persistence.xml');
        $loader->load('two_factor.xml');

        // Load two-factor modules
        if (isset($config['email']['enabled']) && true === $config['email']['enabled']) {
            $this->configureEmailAuthenticationProvider($container, $config);
        }
        if (isset($config['google']['enabled']) && true === $config['google']['enabled']) {
            $this->configureGoogleAuthenticationProvider($container, $config);
        }
        if (isset($config['totp']['enabled']) && true === $config['totp']['enabled']) {
            $this->configureTotpAuthenticationProvider($container, $config);
        }

        // Configure custom services
        $this->configurePersister($container, $config);
        $this->configureIpWhitelistProvider($container, $config);
        $this->configureTokenFactory($container, $config);
        if (isset($config['trusted_device']['enabled']) && true === $config['trusted_device']['enabled']) {
            $this->configureTrustedDeviceManager($container, $config);
        }
        if (isset($config['backup_codes']['enabled']) && true === $config['backup_codes']['enabled']) {
            $this->configureBackupCodeManager($container, $config);
        }
    }

    private function configurePersister(ContainerBuilder $container, array $config): void
    {
        $container->setAlias('scheb_two_factor.persister', $config['persister']);
    }

    private function configureTrustedDeviceManager(ContainerBuilder $container, array $config): void
    {
        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('trusted_device.xml');
        $container->setAlias('scheb_two_factor.trusted_device_manager', $config['trusted_device']['manager']);

        $container->setParameter('scheb_two_factor.trusted_device.enabled', $config['trusted_device']['enabled']);
        $container->setParameter('scheb_two_factor.trusted_device.cookie_name', $config['trusted_device']['cookie_name']);
        $container->setParameter('scheb_two_factor.trusted_device.lifetime', $config['trusted_device']['lifetime']);
        $container->setParameter('scheb_two_factor.trusted_device.extend_lifetime', $config['trusted_device']['extend_lifetime']);
        $container->setParameter('scheb_two_factor.trusted_device.cookie_secure', $config['trusted_device']['cookie_secure']);
        $container->setParameter('scheb_two_factor.trusted_device.cookie_same_site', $config['trusted_device']['cookie_same_site']);
        $container->setParameter('scheb_two_factor.trusted_device.cookie_domain', $config['trusted_device']['cookie_domain']);
        $container->setParameter('scheb_two_factor.trusted_device.cookie_path', $config['trusted_device']['cookie_path']);
    }

    private function configureBackupCodeManager(ContainerBuilder $container, array $config): void
    {
        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('backup_codes.xml');
        $container->setAlias('scheb_two_factor.backup_code_manager', $config['backup_codes']['manager']);
    }

    private function configureIpWhitelistProvider(ContainerBuilder $container, array $config): void
    {
        $container->setAlias('scheb_two_factor.ip_whitelist_provider', $config['ip_whitelist_provider']);
    }

    private function configureTokenFactory(ContainerBuilder $container, array $config): void
    {
        $container->setAlias('scheb_two_factor.token_factory', $config['two_factor_token_factory']);
    }

    private function configureEmailAuthenticationProvider(ContainerBuilder $container, array $config): void
    {
        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('two_factor_provider_email.xml');

        $container->setParameter('scheb_two_factor.email.sender_email', $config['email']['sender_email']);
        $container->setParameter('scheb_two_factor.email.sender_name', $config['email']['sender_name']);
        $container->setParameter('scheb_two_factor.email.template', $config['email']['template']);
        $container->setParameter('scheb_two_factor.email.digits', $config['email']['digits']);
        $container->setAlias('scheb_two_factor.security.email.auth_code_mailer', $config['email']['mailer']);
        $container->setAlias('scheb_two_factor.security.email.code_generator', $config['email']['code_generator']);
    }

    private function configureGoogleAuthenticationProvider(ContainerBuilder $container, array $config): void
    {
        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('two_factor_provider_google.xml');

        $container->setParameter('scheb_two_factor.google.server_name', $config['google']['server_name']);
        $container->setParameter('scheb_two_factor.google.issuer', $config['google']['issuer']);
        $container->setParameter('scheb_two_factor.google.template', $config['google']['template']);
        $container->setParameter('scheb_two_factor.google.digits', $config['google']['digits']);
        $container->setParameter('scheb_two_factor.google.window', $config['google']['window']);
    }

    private function configureTotpAuthenticationProvider(ContainerBuilder $container, array $config): void
    {
        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('two_factor_provider_totp.xml');

        $container->setParameter('scheb_two_factor.totp.issuer', $config['totp']['issuer']);
        $container->setParameter('scheb_two_factor.totp.server_name', $config['totp']['server_name']);
        $container->setParameter('scheb_two_factor.totp.window', $config['totp']['window']);
        $container->setParameter('scheb_two_factor.totp.parameters', $config['totp']['parameters']);
        $container->setParameter('scheb_two_factor.totp.template', $config['totp']['template']);
    }
}
