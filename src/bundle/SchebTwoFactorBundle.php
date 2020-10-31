<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle;

use Scheb\TwoFactorBundle\DependencyInjection\Compiler\AccessListenerCompilerPass;
use Scheb\TwoFactorBundle\DependencyInjection\Compiler\AuthenticationProviderDecoratorCompilerPass;
use Scheb\TwoFactorBundle\DependencyInjection\Compiler\MailerCompilerPass;
use Scheb\TwoFactorBundle\DependencyInjection\Compiler\RememberMeServicesDecoratorCompilerPass;
use Scheb\TwoFactorBundle\DependencyInjection\Compiler\TwoFactorFirewallConfigCompilerPass;
use Scheb\TwoFactorBundle\DependencyInjection\Compiler\TwoFactorProviderCompilerPass;
use Scheb\TwoFactorBundle\DependencyInjection\Factory\Security\AuthenticatorTwoFactorFactory;
use Scheb\TwoFactorBundle\DependencyInjection\Factory\Security\TwoFactorFactory;
use Scheb\TwoFactorBundle\DependencyInjection\Factory\Security\TwoFactorServicesFactory;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AuthenticatorFactoryInterface;
use Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SchebTwoFactorBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new AuthenticationProviderDecoratorCompilerPass());
        $container->addCompilerPass(new RememberMeServicesDecoratorCompilerPass());
        $container->addCompilerPass(new AccessListenerCompilerPass());
        $container->addCompilerPass(new TwoFactorProviderCompilerPass());
        $container->addCompilerPass(new TwoFactorFirewallConfigCompilerPass());
        $container->addCompilerPass(new MailerCompilerPass());

        /** @var SecurityExtension $extension */
        $extension = $container->getExtension('security');

        if (interface_exists(AuthenticatorFactoryInterface::class)) {
            // Compatibility with authenticators in Symfony >= 5.1
            $securityFactory = new AuthenticatorTwoFactorFactory(new TwoFactorServicesFactory());
        } else {
            $securityFactory = new TwoFactorFactory(new TwoFactorServicesFactory());
        }

        $extension->addSecurityListenerFactory($securityFactory);
    }
}
