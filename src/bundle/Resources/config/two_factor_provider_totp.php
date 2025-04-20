<?php

declare(strict_types=1);

use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\DefaultTwoFactorFormRenderer;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticator;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorTwoFactorProvider;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpFactory;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ReferenceConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container->services()

        ->set('scheb_two_factor.security.totp_factory', TotpFactory::class)
            ->public()
            ->args([
                '%scheb_two_factor.totp.server_name%',
                '%scheb_two_factor.totp.issuer%',
                '%scheb_two_factor.totp.parameters%',
                (new ReferenceConfigurator('clock'))->nullOnInvalid(),
            ])

        ->set('scheb_two_factor.security.totp_authenticator', TotpAuthenticator::class)
            ->public()
            ->args([
                service('scheb_two_factor.security.totp_factory'),
                service('event_dispatcher'),
                '%scheb_two_factor.totp.leeway%',
            ])

        ->set('scheb_two_factor.security.totp.default_form_renderer', DefaultTwoFactorFormRenderer::class)
            ->lazy(true)
            ->args([
                service('twig'),
                '%scheb_two_factor.totp.template%',
            ])

        ->set('scheb_two_factor.security.totp.provider', TotpAuthenticatorTwoFactorProvider::class)
            ->tag('scheb_two_factor.provider', ['alias' => 'totp'])
            ->args([
                service('scheb_two_factor.security.totp_authenticator'),
                service('scheb_two_factor.security.totp.form_renderer'),
            ])

        ->alias('scheb_two_factor.security.totp.form_renderer', 'scheb_two_factor.security.totp.default_form_renderer')

        ->alias(TotpAuthenticatorInterface::class, 'scheb_two_factor.security.totp_authenticator')

        ->alias(TotpAuthenticator::class, 'scheb_two_factor.security.totp_authenticator');
};
