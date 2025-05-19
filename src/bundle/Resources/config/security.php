<?php

declare(strict_types=1);

use Scheb\TwoFactorBundle\Security\Authentication\AuthenticationTrustResolver;
use Scheb\TwoFactorBundle\Security\Authorization\TwoFactorAccessDecider;
use Scheb\TwoFactorBundle\Security\Authorization\Voter\TwoFactorInProgressVoter;
use Scheb\TwoFactorBundle\Security\Http\Authentication\DefaultAuthenticationFailureHandler;
use Scheb\TwoFactorBundle\Security\Http\Authentication\DefaultAuthenticationRequiredHandler;
use Scheb\TwoFactorBundle\Security\Http\Authentication\DefaultAuthenticationSuccessHandler;
use Scheb\TwoFactorBundle\Security\Http\Authenticator\TwoFactorAuthenticator;
use Scheb\TwoFactorBundle\Security\Http\EventListener\CheckTwoFactorCodeListener;
use Scheb\TwoFactorBundle\Security\Http\EventListener\CheckTwoFactorCodeReuseListener;
use Scheb\TwoFactorBundle\Security\Http\EventListener\SuppressRememberMeListener;
use Scheb\TwoFactorBundle\Security\Http\EventListener\ThrowExceptionOnTwoFactorCodeReuseListener;
use Scheb\TwoFactorBundle\Security\Http\Firewall\ExceptionListener;
use Scheb\TwoFactorBundle\Security\Http\Firewall\TwoFactorAccessListener;
use Scheb\TwoFactorBundle\Security\Http\Utils\RequestDataReader;
use Scheb\TwoFactorBundle\Security\TwoFactor\Csrf\NullCsrfTokenManager;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\AuthenticationSuccessEventSuppressor;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\AuthenticationTokenListener;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorFormListener;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderPreparationListener;
use Scheb\TwoFactorBundle\Security\TwoFactor\TwoFactorFirewallConfig;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\abstract_arg;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('scheb_two_factor.security.authenticator', TwoFactorAuthenticator::class)
            ->tag('monolog.logger', ['channel' => 'security'])
            ->args([
                abstract_arg('Two-factor firewall config'),
                service('security.token_storage'),
                abstract_arg('Authentication success handler'),
                abstract_arg('Authentication failure handler'),
                abstract_arg('Authentication required handler'),
                service('event_dispatcher'),
                service('logger')->nullOnInvalid(),
            ])

        ->set('scheb_two_factor.security.authentication.trust_resolver', AuthenticationTrustResolver::class)
            ->decorate('security.authentication.trust_resolver')
            ->args([service('scheb_two_factor.security.authentication.trust_resolver.inner')])

        ->set('scheb_two_factor.security.access.authenticated_voter', TwoFactorInProgressVoter::class)
            ->tag('security.voter', ['priority' => 249])

        ->set('scheb_two_factor.security.access.access_decider', TwoFactorAccessDecider::class)
            ->args([
                service('security.access_map'),
                service('security.access.decision_manager'),
                service('security.http_utils'),
                service('security.logout_url_generator'),
            ])

        ->set('scheb_two_factor.security.listener.token_created', AuthenticationTokenListener::class)
            ->args([
                abstract_arg('Firewall name'),
                service('scheb_two_factor.condition_registry'),
                service('scheb_two_factor.provider_initiator'),
                service('scheb_two_factor.authentication_context_factory'),
                service('request_stack'),
            ])

        ->set('scheb_two_factor.security.listener.check_two_factor_code', CheckTwoFactorCodeListener::class)
            ->tag('kernel.event_subscriber')
            ->args([
                service('scheb_two_factor.provider_preparation_recorder'),
                service('scheb_two_factor.provider_registry'),
                service('event_dispatcher'),
            ])
        ->set('scheb_two_factor.security.listener.check_two_factor_code_reuse', CheckTwoFactorCodeReuseListener::class)
            ->tag('kernel.event_subscriber')
            ->args([
                service('event_dispatcher'),
                service('scheb_two_factor.code_reuse_cache')->nullOnInvalid(),
                '%scheb_two_factor.code_reuse_cache_duration%',
                service('logger')->nullOnInvalid(),
            ])

        ->set('scheb_two_factor.security.listener.throw_exception_on_two_factor_code_reuse', ThrowExceptionOnTwoFactorCodeReuseListener::class)
            ->tag('kernel.event_subscriber')

        ->set('scheb_two_factor.security.listener.suppress_remember_me', SuppressRememberMeListener::class)
            ->tag('kernel.event_subscriber')

        ->set('scheb_two_factor.security.provider_preparation_listener', TwoFactorProviderPreparationListener::class)
            ->args([
                service('scheb_two_factor.provider_registry'),
                service('scheb_two_factor.provider_preparation_recorder'),
                service('logger')->nullOnInvalid(),
                abstract_arg('Firewall name'),
                false, // Prepare on login setting
                false, // Prepare on access denied setting
            ])

        ->set('scheb_two_factor.security.form_listener', TwoFactorFormListener::class)
            ->args([
                abstract_arg('Two-factor firewall config'),
                service('security.token_storage'),
                service('event_dispatcher'),
            ])

        ->set('scheb_two_factor.security.authentication_success_event_suppressor', AuthenticationSuccessEventSuppressor::class)
            ->tag('kernel.event_subscriber')

        ->set('scheb_two_factor.security.kernel_exception_listener', ExceptionListener::class)
            ->args([
                abstract_arg('Firewall name'),
                service('security.token_storage'),
                abstract_arg('Authentication required handler'),
                service('event_dispatcher'),
            ])

        ->set('scheb_two_factor.security.access_listener', TwoFactorAccessListener::class)
            ->args([
                abstract_arg('Two-factor firewall config'),
                service('security.token_storage'),
                service('scheb_two_factor.security.access.access_decider'),
            ])

        ->set('scheb_two_factor.security.authentication.success_handler', DefaultAuthenticationSuccessHandler::class)
            ->args([
                service('security.http_utils'),
                abstract_arg('Two-factor firewall config'),
            ])

        ->set('scheb_two_factor.security.authentication.failure_handler', DefaultAuthenticationFailureHandler::class)
            ->args([
                service('security.http_utils'),
                abstract_arg('Two-factor firewall config'),
            ])

        ->set('scheb_two_factor.security.authentication.authentication_required_handler', DefaultAuthenticationRequiredHandler::class)
            ->args([
                service('security.http_utils'),
                abstract_arg('Two-factor firewall config'),
            ])

        ->set('scheb_two_factor.null_csrf_token_manager', NullCsrfTokenManager::class)

        ->set('scheb_two_factor.security.firewall_config', TwoFactorFirewallConfig::class)
            ->args([
                [], // Firewall settings
                abstract_arg('Firewall name'),
                service('security.http_utils'),
                service('scheb_two_factor.security.request_data_reader'),
            ])

        ->set('scheb_two_factor.security.request_data_reader', RequestDataReader::class)

        ->alias('scheb_two_factor.csrf_token_manager', 'security.csrf.token_manager');
};
