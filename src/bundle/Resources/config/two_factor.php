<?php

declare(strict_types=1);

use Scheb\TwoFactorBundle\Controller\FormController;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenFactory;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContext;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextFactory;
use Scheb\TwoFactorBundle\Security\TwoFactor\Condition\AuthenticatedTokenCondition;
use Scheb\TwoFactorBundle\Security\TwoFactor\Condition\IpWhitelistCondition;
use Scheb\TwoFactorBundle\Security\TwoFactor\Condition\TwoFactorConditionRegistry;
use Scheb\TwoFactorBundle\Security\TwoFactor\IpWhitelist\DefaultIpWhitelistProvider;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\DefaultTwoFactorFormRenderer;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TokenPreparationRecorder;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorFormRendererInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderDecider;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderInitiator;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderRegistry;
use Scheb\TwoFactorBundle\Security\TwoFactor\TwoFactorFirewallContext;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\abstract_arg;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('scheb_two_factor.provider_registry', TwoFactorProviderRegistry::class)
            ->args([
                abstract_arg('Two-factor providers'),
            ])

        ->set('scheb_two_factor.default_token_factory', TwoFactorTokenFactory::class)

        ->set('scheb_two_factor.default_provider_decider', TwoFactorProviderDecider::class)

        ->set('scheb_two_factor.authentication_context_factory', AuthenticationContextFactory::class)
            ->args([AuthenticationContext::class])

        ->set('scheb_two_factor.condition_registry', TwoFactorConditionRegistry::class)
            ->lazy(true)
            ->args([
                abstract_arg('Two-factor conditions'),
            ])

        ->set('scheb_two_factor.authenticated_token_condition', AuthenticatedTokenCondition::class)
            ->lazy(true)
            ->args(['%scheb_two_factor.security_tokens%'])

        ->set('scheb_two_factor.ip_whitelist_condition', IpWhitelistCondition::class)
            ->lazy(true)
            ->args([
                service('scheb_two_factor.ip_whitelist_provider'),
            ])

        ->set('scheb_two_factor.default_ip_whitelist_provider', DefaultIpWhitelistProvider::class)
            ->args(['%scheb_two_factor.ip_whitelist%'])

        ->set('scheb_two_factor.provider_initiator', TwoFactorProviderInitiator::class)
            ->lazy(true)
            ->args([
                service('scheb_two_factor.provider_registry'),
                service('scheb_two_factor.token_factory'),
                service('scheb_two_factor.provider_decider'),
            ])

        ->set('scheb_two_factor.firewall_context', TwoFactorFirewallContext::class)
            ->public()
            ->args([abstract_arg('Firewall configs')])

        ->set('scheb_two_factor.provider_preparation_recorder', TokenPreparationRecorder::class)
            ->args([service('security.token_storage')])

        ->set('scheb_two_factor.form_controller', FormController::class)
            ->public()
            ->args([
                service('security.token_storage'),
                service('scheb_two_factor.provider_registry'),
                service('scheb_two_factor.firewall_context'),
                service('security.logout_url_generator'),
                service('scheb_two_factor.trusted_device_manager')->nullOnInvalid(),
                '%scheb_two_factor.trusted_device.enabled%',
            ])

        ->set('scheb_two_factor.security.form_renderer', DefaultTwoFactorFormRenderer::class)
            ->lazy(true)
            ->args([
                service('twig'),
                '@SchebTwoFactor/Authentication/form.html.twig',
            ])

        ->alias(TwoFactorFirewallContext::class, 'scheb_two_factor.firewall_context')

        ->alias(TwoFactorFormRendererInterface::class, 'scheb_two_factor.security.form_renderer');
};
