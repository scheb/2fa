<?php

namespace Scheb\TwoFactorBundle\DependencyInjection;

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
                ->arrayNode('trusted_device')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')->defaultFalse()->end()
                        ->scalarNode('manager')->defaultNull()->end()
                        ->integerNode('lifetime')->defaultValue(60 * 24 * 3600)->min(1)->end()
                        ->booleanNode('extend_lifetime')->defaultValue(false)->end()
                        ->scalarNode('cookie_name')->defaultValue('trusted_device')->end()
                        ->booleanNode('cookie_secure')->defaultValue(false)->end()
                        ->scalarNode('cookie_same_site')
                            ->defaultValue('lax')
                            ->validate()
                            ->ifNotInArray(['lax', 'strict', null])
                                ->thenInvalid('Invalid cookie same-site value %s, must be "lax", "strict" or null')
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('backup_codes')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')->defaultFalse()->end()
                        ->scalarNode('manager')->defaultNull()->end()
                    ->end()
                ->end()
                ->arrayNode('email')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')->defaultFalse()->end()
                        ->scalarNode('mailer')->defaultNull()->end()
                        ->scalarNode('code_generator')->defaultNull()->end()
                        ->scalarNode('sender_email')->defaultValue('no-reply@example.com')->end()
                        ->scalarNode('sender_name')->defaultNull()->end()
                        ->scalarNode('template')->defaultValue('@SchebTwoFactor/Authentication/form.html.twig')->end()
                        ->integerNode('digits')->defaultValue(4)->min(1)->end()
                    ->end()
                ->end()
                ->arrayNode('google')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')->defaultFalse()->end()
                        ->scalarNode('issuer')->defaultNull()->end()
                        ->scalarNode('server_name')->defaultNull()->end()
                        ->scalarNode('template')->defaultValue('@SchebTwoFactor/Authentication/form.html.twig')->end()
                        ->integerNode('digits')->defaultValue(6)->min(1)->end()
                    ->end()
                ->end()
                ->arrayNode('security_tokens')
                    ->defaultValue(["Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken"])
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

        return $treeBuilder;
    }
}
