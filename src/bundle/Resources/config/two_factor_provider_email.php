<?php

declare(strict_types=1);

use Scheb\TwoFactorBundle\Mailer\SymfonyAuthCodeMailer;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\DefaultTwoFactorFormRenderer;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Email\EmailTwoFactorProvider;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Email\Generator\CodeGenerator;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Email\Generator\CodeGeneratorInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('scheb_two_factor.security.email.symfony_auth_code_mailer', SymfonyAuthCodeMailer::class)
            ->args([
                service('mailer.mailer'),
                '%scheb_two_factor.email.sender_email%',
                '%scheb_two_factor.email.sender_name%',
            ])

        ->set('scheb_two_factor.security.email.default_code_generator', CodeGenerator::class)
            ->lazy(true)
            ->args([
                service('scheb_two_factor.persister'),
                service('scheb_two_factor.security.email.auth_code_mailer'),
                '%scheb_two_factor.email.digits%',
            ])

        ->set('scheb_two_factor.security.email.default_form_renderer', DefaultTwoFactorFormRenderer::class)
            ->lazy(true)
            ->args([
                service('twig'),
                '%scheb_two_factor.email.template%',
            ])

        ->set('scheb_two_factor.security.email.provider', EmailTwoFactorProvider::class)
            ->tag('scheb_two_factor.provider', ['alias' => 'email'])
            ->args([
                service('scheb_two_factor.security.email.code_generator'),
                service('scheb_two_factor.security.email.form_renderer'),
                service('event_dispatcher'),
            ])

        ->alias(CodeGeneratorInterface::class, 'scheb_two_factor.security.email.code_generator')

        ->alias('scheb_two_factor.security.email.form_renderer', 'scheb_two_factor.security.email.default_form_renderer');
};
