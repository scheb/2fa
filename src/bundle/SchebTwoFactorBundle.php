<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle;

use Scheb\TwoFactorBundle\DependencyInjection\Compiler\AuthenticationProviderDecoratorCompilerPass;
use Scheb\TwoFactorBundle\DependencyInjection\Compiler\MailerCompilerPass;
use Scheb\TwoFactorBundle\DependencyInjection\Compiler\TwoFactorFirewallConfigCompilerPass;
use Scheb\TwoFactorBundle\DependencyInjection\Compiler\TwoFactorProviderCompilerPass;
use Scheb\TwoFactorBundle\DependencyInjection\Factory\Security\TwoFactorFactory;
use Scheb\TwoFactorBundle\DependencyInjection\Factory\Security\TwoFactorServicesFactory;
use Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @final
 */
class SchebTwoFactorBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new AuthenticationProviderDecoratorCompilerPass());
        $container->addCompilerPass(new TwoFactorProviderCompilerPass());
        $container->addCompilerPass(new TwoFactorFirewallConfigCompilerPass());
        $container->addCompilerPass(new MailerCompilerPass());

        /** @var SecurityExtension $extension */
        $extension = $container->getExtension('security');

        $securityFactory = new TwoFactorFactory(new TwoFactorServicesFactory());
        $extension->addAuthenticatorFactory($securityFactory);
    }
}
