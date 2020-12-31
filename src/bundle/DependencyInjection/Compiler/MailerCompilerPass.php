<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;

/**
 * Determine the default mailer to use.
 *
 * @final
 */
class MailerCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('scheb_two_factor.security.email.provider')) {
            // Email authentication is not enabled
            return;
        }

        if ($container->hasAlias('scheb_two_factor.security.email.auth_code_mailer')) {
            // Custom AuthCodeMailer
            return;
        }

        if ($container->hasDefinition('mailer.mailer')) {
            $container->setAlias('scheb_two_factor.security.email.auth_code_mailer', 'scheb_two_factor.security.email.symfony_auth_code_mailer');
        } elseif ($container->hasDefinition('swiftmailer.mailer.default')) {
            $container->setAlias('scheb_two_factor.security.email.auth_code_mailer', 'scheb_two_factor.security.email.swift_auth_code_mailer');
        } else {
            $message = 'Could not determine default mailer service to use. '.
                'Please install one of symfony/swiftmailer-bundle or symfony/mailer. '.
                'Or create your own mailer and configure it under "scheb_two_factor.email.mailer".';
            throw new LogicException($message);
        }
    }
}
