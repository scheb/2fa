<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\DependencyInjection;

use Scheb\TwoFactorBundle\Model\BackupCodeInterface;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface as EMailTwoFactorInterface;
use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface as GoogleTwoFactorInterface;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface as TotpTwoFactorInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('scheb_two_factor');

        if (method_exists($treeBuilder, 'getRootNode')) {
            $rootNode = $treeBuilder->getRootNode();
        } else {
            // BC layer for symfony/config 4.1 and older
            $rootNode = $treeBuilder->root('scheb_two_factor');
        }

        $rootNode
            ->children()
                ->scalarNode('persister')->defaultNull()->end()
                ->scalarNode('model_manager_name')->defaultNull()->end()
                ->arrayNode('security_tokens')
                    ->defaultValue([
                        "Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken",
                        "Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken",
                    ])
                    ->prototype('scalar')->end()
                ->end()
                ->arrayNode('ip_whitelist')
                    ->defaultValue([])
                    ->prototype('scalar')->end()
                ->end()
            ->scalarNode('ip_whitelist_provider')->defaultNull()->end()
            ->scalarNode('two_factor_token_factory')->defaultNull()->end()
            ->end()
        ;
        $this->addTrustedDeviceConfiguration($rootNode);
        $this->addBackupCodeConfiguration($rootNode);
        $this->addEMailConfiguration($rootNode);
        $this->addGoogleAuthenticatorConfiguration($rootNode);
        $this->addTotpConfiguration($rootNode);

        return $treeBuilder;
    }

    private function addBackupCodeConfiguration(ArrayNodeDefinition $rootNode): void
    {
        if (!interface_exists(BackupCodeInterface::class)) {
            return;
        }
        $rootNode
            ->children()
                ->arrayNode('backup_codes')
                    ->canBeEnabled()
                    ->children()
                        ->scalarNode('manager')->defaultValue('scheb_two_factor.default_backup_code_manager')->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    private function addTrustedDeviceConfiguration(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('trusted_device')
                    ->canBeEnabled()
                    ->children()
                        ->scalarNode('manager')->defaultValue('scheb_two_factor.default_trusted_device_manager')->end()
                        ->integerNode('lifetime')->defaultValue(60 * 24 * 3600)->min(1)->end()
                        ->booleanNode('extend_lifetime')->defaultFalse()->end()
                        ->scalarNode('cookie_name')->defaultValue('trusted_device')->end()
                        ->booleanNode('cookie_secure')->defaultFalse()->end()
                        ->scalarNode('cookie_domain')->defaultNull()->end()
                        ->scalarNode('cookie_path')->defaultValue('/')->end()
                        ->scalarNode('cookie_same_site')
                            ->defaultValue('lax')
                            ->validate()
                                ->ifNotInArray(['lax', 'strict', null])
                                ->thenInvalid('Invalid cookie same-site value %s, must be "lax", "strict" or null')
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    private function addEMailConfiguration(ArrayNodeDefinition $rootNode): void
    {
        if (!interface_exists(EMailTwoFactorInterface::class)) {
            return;
        }
        $rootNode
            ->children()
                ->arrayNode('email')
                    ->canBeEnabled()
                    ->children()
                        ->scalarNode('mailer')->defaultValue('scheb_two_factor.security.email.default_auth_code_mailer')->end()
                        ->scalarNode('code_generator')->defaultValue('scheb_two_factor.security.email.default_code_generator')->end()
                        ->scalarNode('sender_email')->defaultValue('no-reply@example.com')->end()
                        ->scalarNode('sender_name')->defaultNull()->end()
                        ->scalarNode('template')->defaultValue('@SchebTwoFactor/Authentication/form.html.twig')->end()
                        ->integerNode('digits')->defaultValue(4)->min(1)->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    private function addTotpConfiguration(ArrayNodeDefinition $rootNode): void
    {
        if (!interface_exists(TotpTwoFactorInterface::class)) {
            return;
        }
        $rootNode
            ->children()
                ->arrayNode('totp')
                    ->canBeEnabled()
                    ->children()
                        ->scalarNode('issuer')->defaultNull()->end()
                        ->scalarNode('server_name')->defaultNull()->end()
                        ->integerNode('window')->defaultValue(1)->min(0)->end()
                        ->arrayNode('parameters')
                            ->scalarPrototype()->end()
                        ->end()
                        ->scalarNode('template')->defaultValue('@SchebTwoFactor/Authentication/form.html.twig')->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    private function addGoogleAuthenticatorConfiguration(ArrayNodeDefinition $rootNode): void
    {
        if (!interface_exists(GoogleTwoFactorInterface::class)) {
            return;
        }
        $rootNode
            ->children()
                ->arrayNode('google')
                    ->canBeEnabled()
                    ->children()
                        ->scalarNode('issuer')->defaultNull()->end()
                        ->scalarNode('server_name')->defaultNull()->end()
                        ->scalarNode('template')->defaultValue('@SchebTwoFactor/Authentication/form.html.twig')->end()
                        ->integerNode('digits')->defaultValue(6)->min(1)->end()
                        ->integerNode('window')->defaultValue(1)->min(0)->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
}
