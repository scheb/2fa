<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\DependencyInjection;

use RecursiveArrayIterator;
use RecursiveIteratorIterator;
use Scheb\TwoFactorBundle\Model\BackupCodeInterface;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface as EMailTwoFactorInterface;
use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface as GoogleTwoFactorInterface;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface as TotpTwoFactorInterface;
use Scheb\TwoFactorBundle\Model\TrustedDeviceInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;
use function interface_exists;
use function iterator_to_array;

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
         * @psalm-suppress UndefinedMethod
         * @psalm-suppress UndefinedInterfaceMethod
         */
        $rootNode
            ->children()
                ->scalarNode('persister')->defaultValue('scheb_two_factor.persister.doctrine')->end()
                ->scalarNode('model_manager_name')->defaultNull()->end()
                ->arrayNode('security_tokens')
                    ->defaultValue([
                        UsernamePasswordToken::class,
                        PostAuthenticationToken::class,
                    ])
                    ->prototype('scalar')->end()
                ->end()
                ->arrayNode('ip_whitelist')
                    ->beforeNormalization()
                        ->ifArray()
                        ->then(static fn (array $value): array => iterator_to_array(new RecursiveIteratorIterator(new RecursiveArrayIterator($value)), false))
                    ->end()
                    ->defaultValue([])
                    ->prototype('scalar')->end()
                ->end()
                ->scalarNode('ip_whitelist_provider')->defaultValue('scheb_two_factor.default_ip_whitelist_provider')->end()
                ->scalarNode('two_factor_token_factory')->defaultValue('scheb_two_factor.default_token_factory')->end()
                ->scalarNode('two_factor_provider_decider')->defaultValue('scheb_two_factor.default_provider_decider')->end()
                ->scalarNode('two_factor_condition')->defaultNull()->end()
                ->scalarNode('code_reuse_cache')->defaultNull()->end()
                ->integerNode('code_reuse_cache_duration')->defaultValue(60)->end()
                ->scalarNode('code_reuse_default_handler')->defaultNull()->end()
            ->end();

        /** @psalm-suppress ArgumentTypeCoercion */
        $this->addExtraConfiguration($rootNode);

        return $treeBuilder;
    }

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
         * @psalm-suppress UndefinedMethod
         * @psalm-suppress UndefinedInterfaceMethod
         * @psalm-suppress PossiblyNullReference
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
            ->end();
    }

    private function addTrustedDeviceConfiguration(ArrayNodeDefinition $rootNode): void
    {
        if (!interface_exists(TrustedDeviceInterface::class)) {
            return;
        }

        /**
         * @psalm-suppress UndefinedMethod
         * @psalm-suppress UndefinedInterfaceMethod
         * @psalm-suppress PossiblyNullReference
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
                        ->scalarNode('key')->defaultNull()->end()
                        ->scalarNode('cookie_name')->defaultValue('trusted_device')->end()
                        ->enumNode('cookie_secure')->values([true, false, 'auto'])->defaultValue('auto')->end()
                        ->scalarNode('cookie_domain')->defaultNull()->end()
                        ->scalarNode('cookie_path')->defaultValue('/')->end()
                        ->scalarNode('cookie_same_site')
                            ->defaultValue('lax')
                            ->validate()
                                ->ifNotInArray(['lax', 'strict', 'none', null])
                                ->thenInvalid('Invalid cookie same-site value %s, must be "lax", "strict" or null')
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    private function addEmailConfiguration(ArrayNodeDefinition $rootNode): void
    {
        if (!interface_exists(EMailTwoFactorInterface::class)) {
            return;
        }

        /**
         * @psalm-suppress UndefinedMethod
         * @psalm-suppress UndefinedInterfaceMethod
         * @psalm-suppress PossiblyNullReference
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
                        ->scalarNode('sender_email')->defaultNull()->end()
                        ->scalarNode('sender_name')->defaultNull()->end()
                        ->scalarNode('template')->defaultValue('@SchebTwoFactor/Authentication/form.html.twig')->end()
                        ->integerNode('digits')->defaultValue(4)->min(1)->end()
                    ->end()
                ->end()
            ->end();
    }

    private function addTotpConfiguration(ArrayNodeDefinition $rootNode): void
    {
        if (!interface_exists(TotpTwoFactorInterface::class)) {
            return;
        }

        /**
         * @psalm-suppress UndefinedMethod
         * @psalm-suppress UndefinedInterfaceMethod
         * @psalm-suppress PossiblyNullReference
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
                        ->integerNode('leeway')->defaultValue(0)->min(0)->end()
                        ->arrayNode('parameters')
                            ->scalarPrototype()->end()
                        ->end()
                        ->scalarNode('template')->defaultValue('@SchebTwoFactor/Authentication/form.html.twig')->end()
                    ->end()
                ->end()
            ->end();
    }

    private function addGoogleAuthenticatorConfiguration(ArrayNodeDefinition $rootNode): void
    {
        if (!interface_exists(GoogleTwoFactorInterface::class)) {
            return;
        }

        /**
         * @psalm-suppress UndefinedMethod
         * @psalm-suppress UndefinedInterfaceMethod
         * @psalm-suppress PossiblyNullReference
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
                        ->integerNode('leeway')->defaultValue(0)->min(0)->end()
                    ->end()
                ->end()
            ->end();
    }
}
