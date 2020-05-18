<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\DependencyInjection\Compiler;

use Scheb\TwoFactorBundle\DependencyInjection\Compiler\AccessListenerCompilerPass;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Bundle\SecurityBundle\Security\FirewallContext;
use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;

class AccessListenerCompilerPassTest extends TestCase
{
    /**
     * @var AccessListenerCompilerPass
     */
    private $compilerPass;

    /**
     * @var ContainerBuilder
     */
    private $container;

    /**
     * @var Definition
     */
    private $firewallContextDefinition;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->compilerPass = new AccessListenerCompilerPass();

        $this->firewallContextDefinition = new Definition(FirewallContext::class);
        $this->container->setDefinition('security.firewall.map.context.firewallName', $this->firewallContextDefinition);
    }

    private function stubTaggedContainerService(array $taggedServices): void
    {
        foreach ($taggedServices as $id => $tags) {
            $definition = $this->container->register($id);

            foreach ($tags as $attributes) {
                $definition->addTag('scheb_two_factor.access_listener', $attributes);
            }
        }
    }

    private function stubFirewallContextListeners($listenersArg): void
    {
        $this->firewallContextDefinition->setArgument(0, $listenersArg);
    }

    private function assertFirewallContextListeners(IteratorArgument $expected): void
    {
        $listenersArgument = $this->firewallContextDefinition->getArgument(0);
        $this->assertEquals($expected, $listenersArgument);
    }

    /**
     * @test
     */
    public function process_missingAlias_throwException(): void
    {
        $taggedServices = [
            'serviceId' => [
                0 => [],
            ],
        ];
        $this->stubTaggedContainerService($taggedServices);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tag "scheb_two_factor.access_listener" requires attribute "firewall" to be set');
        $this->compilerPass->process($this->container);
    }

    /**
     * @test
     */
    public function process_invalidListenersArgument_throwException(): void
    {
        $taggedServices = [
            'serviceId' => [
                0 => ['firewall' => 'firewallName'],
            ],
        ];
        $this->stubTaggedContainerService($taggedServices);
        $this->stubFirewallContextListeners([]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot inject access listener');
        $this->compilerPass->process($this->container);
    }

    /**
     * @test
     */
    public function process_requirementsFulfilled_addAccessListener(): void
    {
        $taggedServices = [
            'serviceId' => [
                0 => ['firewall' => 'firewallName'],
            ],
        ];

        $this->stubTaggedContainerService($taggedServices);
        $this->stubFirewallContextListeners(new IteratorArgument([
            new Reference('firewallListener1'),
            new Reference('firewallListener2'),
        ]));

        $this->compilerPass->process($this->container);

        $this->assertFirewallContextListeners(new IteratorArgument([
            new Reference('firewallListener1'),
            new Reference('firewallListener2'),
            new Reference('serviceId'),
        ]));
    }
}
