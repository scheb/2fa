<?php

declare(strict_types=1);

use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\DefaultTwoFactorFormRenderer;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticator;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorTwoFactorProvider;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleTotpFactory;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ReferenceConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container->services()

        ->set('scheb_two_factor.security.google_totp_factory', GoogleTotpFactory::class)
            ->args([
                '%scheb_two_factor.google.server_name%',
                '%scheb_two_factor.google.issuer%',
                '%scheb_two_factor.google.digits%',
                (new ReferenceConfigurator('clock'))->nullOnInvalid(),
            ])

        ->set('scheb_two_factor.security.google_authenticator', GoogleAuthenticator::class)
            ->public()
            ->args([
                service('scheb_two_factor.security.google_totp_factory'),
                service('event_dispatcher'),
                '%scheb_two_factor.google.leeway%',
            ])

        ->set('scheb_two_factor.security.google.default_form_renderer', DefaultTwoFactorFormRenderer::class)
            ->lazy(true)
            ->args([
                service('twig'),
                '%scheb_two_factor.google.template%',
            ])

        ->set('scheb_two_factor.security.google.provider', GoogleAuthenticatorTwoFactorProvider::class)
            ->tag('scheb_two_factor.provider', ['alias' => 'google'])
            ->args([
                service('scheb_two_factor.security.google_authenticator'),
                service('scheb_two_factor.security.google.form_renderer'),
            ])

        ->alias('scheb_two_factor.security.google.form_renderer', 'scheb_two_factor.security.google.default_form_renderer')

        ->alias(GoogleAuthenticatorInterface::class, 'scheb_two_factor.security.google_authenticator')

        ->alias(GoogleAuthenticator::class, 'scheb_two_factor.security.google_authenticator');
};
