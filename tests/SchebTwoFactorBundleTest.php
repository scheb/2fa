<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests;

use Scheb\TwoFactorBundle\DependencyInjection\Compiler\AccessListenerCompilerPass;
use Scheb\TwoFactorBundle\DependencyInjection\Compiler\AuthenticationProviderDecoratorCompilerPass;
use Scheb\TwoFactorBundle\DependencyInjection\Compiler\MailerCompilerPass;
use Scheb\TwoFactorBundle\DependencyInjection\Compiler\RememberMeServicesDecoratorCompilerPass;
use Scheb\TwoFactorBundle\DependencyInjection\Compiler\TwoFactorFirewallConfigCompilerPass;
use Scheb\TwoFactorBundle\DependencyInjection\Compiler\TwoFactorProviderCompilerPass;
use Scheb\TwoFactorBundle\DependencyInjection\Factory\Security\TwoFactorFactory;
use Scheb\TwoFactorBundle\SchebTwoFactorBundle;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\FirewallListenerFactoryInterface;
use Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SchebTwoFactorBundleTest extends TestCase
{
    /**
     * @test
     */
    public function build_initializeBundle_addCompilerPass(): void
    {
        $containerBuilder = $this->createMock(ContainerBuilder::class);

        $compilerPasses = [
            $this->isInstanceOf(AuthenticationProviderDecoratorCompilerPass::class),
            $this->isInstanceOf(RememberMeServicesDecoratorCompilerPass::class),
            $this->isInstanceOf(TwoFactorProviderCompilerPass::class),
            $this->isInstanceOf(TwoFactorFirewallConfigCompilerPass::class),
            $this->isInstanceOf(MailerCompilerPass::class),
        ];

        // Compatibility for Symfony <= 5.1
        // From Symfony 5.2 on the bundle uses FirewallListenerFactoryInterface to inject its TwoFactorAccessListener
        if (!interface_exists(FirewallListenerFactoryInterface::class)) {
            $compilerPasses[] = $this->isInstanceOf(AccessListenerCompilerPass::class);
        }

        //Expect compiler pass to be added
        $containerBuilder
            ->expects($this->exactly(\count($compilerPasses)))
            ->method('addCompilerPass')
            ->with($this->logicalOr(...$compilerPasses));

        //Expect register authentication provider factory
        $securityExtension = $this->createMock(SecurityExtension::class);
        $containerBuilder
            ->expects($this->once())
            ->method('getExtension')
            ->with('security')
            ->willReturn($securityExtension);
        $securityExtension
            ->expects($this->once())
            ->method('addSecurityListenerFactory')
            ->with($this->isInstanceOf(TwoFactorFactory::class));

        $bundle = new SchebTwoFactorBundle();
        $bundle->build($containerBuilder);
    }
}
