<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\DependencyInjection;

use Scheb\TwoFactorBundle\Model\BackupCodeInterface;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface as EMailTwoFactorInterface;
use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface as GoogleTwoFactorInterface;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface as TotpTwoFactorInterface;
use Scheb\TwoFactorBundle\Model\TrustedDeviceInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;

/**
 * @final
 */
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('scheb_two_factor');
        $rootNode = $treeBuilder->getRootNode();

        /**
         * @psalm-suppress PossiblyNullReference
         * @psalm-suppress PossiblyUndefinedMethod
         */
        $rootNode
            ->children()
                ->scalarNode('persister')->defaultValue('scheb_two_factor.persister.doctrine')->end()
                ->scalarNode('model_manager_name')->defaultNull()->end()
                ->arrayNode('security_tokens')
                    ->defaultValue([
                        UsernamePasswordToken::class,
                        PostAuthenticationGuardToken::class,
                        PostAuthenticationToken::class,
                    ])
                    ->prototype('scalar')->end()
                ->end()
                ->arrayNode('ip_whitelist')
                    ->defaultValue([])
                    ->prototype('scalar')->end()
                ->end()
                ->scalarNode('ip_whitelist_provider')->defaultValue('scheb_two_factor.default_ip_whitelist_provider')->end()
                ->scalarNode('two_factor_token_factory')->defaultValue('scheb_two_factor.default_token_factory')->end()
                ->scalarNode('two_factor_condition')->defaultNull()->end()
            ->end()
        ;

        /** @psalm-suppress ArgumentTypeCoercion */
        $this->addExtraConfiguration($rootNode);

        return $treeBuilder;
    }

    /**
     * @psalm-suppress ArgumentTypeCoercion
     */
    private function addExtraConfiguration(ArrayNodeDefinition $rootNode): void
    {
        $this->addTrustedDeviceConfiguration($rootNode);
        $this->addBackupCodeConfiguration($rootNode);
        $this->addEmailConfiguration($rootNode);
        $this->addGoogleAuthenticatorConfiguration($rootNode);
        $this->addTotpConfiguration($rootNode);
    }

    private function addBackupCodeConfiguration(ArrayNodeDefinition $rootNode): void
    {
        if (!interface_exists(BackupCodeInterface::class)) {
            return;
        }

        /**
         * @psalm-suppress PossiblyNullReference
         * @psalm-suppress PossiblyUndefinedMethod
         */
        $rootNode
            ->children()
                ->arrayNode('backup_codes')
                ->canBeEnabled()
                ->children()
                        ->scalarNode('enabled')->defaultValue(false)->end()
                        ->scalarNode('manager')->defaultValue('scheb_two_factor.default_backup_code_manager')->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    private function addTrustedDeviceConfiguration(ArrayNodeDefinition $rootNode): void
    {
        if (!interface_exists(TrustedDeviceInterface::class)) {
            return;
        }

        /**
         * @psalm-suppress PossiblyNullReference
         * @psalm-suppress PossiblyUndefinedMethod
         */
        $rootNode
            ->children()
                ->arrayNode('trusted_device')
                    ->canBeEnabled()
                    ->children()
                        ->scalarNode('enabled')->defaultValue(false)->end()
                        ->scalarNode('manager')->defaultValue('scheb_two_factor.default_trusted_device_manager')->end()
                        ->integerNode('lifetime')->defaultValue(60 * 24 * 3600)->min(1)->end()
                        ->booleanNode('extend_lifetime')->defaultFalse()->end()
                        ->scalarNode('cookie_name')->defaultValue('trusted_device')->end()
                        ->enumNode('cookie_secure')->values([true, false, 'auto'])->defaultValue('auto')->end()
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

    private function addEmailConfiguration(ArrayNodeDefinition $rootNode): void
    {
        if (!interface_exists(EMailTwoFactorInterface::class)) {
            return;
        }

        /**
         * @psalm-suppress PossiblyNullReference
         * @psalm-suppress PossiblyUndefinedMethod
         */
        $rootNode
            ->children()
                ->arrayNode('email')
                    ->canBeEnabled()
                    ->children()
                        ->scalarNode('enabled')->defaultValue(false)->end()
                        ->scalarNode('mailer')->defaultNull()->end()
                        ->scalarNode('code_generator')->defaultValue('scheb_two_factor.security.email.default_code_generator')->end()
                        ->scalarNode('form_renderer')->defaultNull()->end()
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

        /**
         * @psalm-suppress PossiblyNullReference
         * @psalm-suppress PossiblyUndefinedMethod
         */
        $rootNode
            ->children()
                ->arrayNode('totp')
                    ->canBeEnabled()
                    ->children()
                        ->scalarNode('enabled')->defaultValue(false)->end()
                        ->scalarNode('form_renderer')->defaultNull()->end()
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

        /**
         * @psalm-suppress PossiblyNullReference
         * @psalm-suppress PossiblyUndefinedMethod
         */
        $rootNode
            ->children()
                ->arrayNode('google')
                    ->canBeEnabled()
                    ->children()
                        ->scalarNode('enabled')->defaultValue(false)->end()
                        ->scalarNode('form_renderer')->defaultNull()->end()
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
