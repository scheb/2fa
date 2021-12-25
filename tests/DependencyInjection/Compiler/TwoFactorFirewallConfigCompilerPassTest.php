<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\DependencyInjection\Compiler;

use Scheb\TwoFactorBundle\DependencyInjection\Compiler\TwoFactorFirewallConfigCompilerPass;
use Scheb\TwoFactorBundle\Security\TwoFactor\TwoFactorFirewallContext;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;

class TwoFactorFirewallConfigCompilerPassTest extends TestCase
{
    private TwoFactorFirewallConfigCompilerPass $compilerPass;
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->compilerPass = new TwoFactorFirewallConfigCompilerPass();

        $firewallContextDefinition = new Definition(TwoFactorFirewallContext::class);
        $firewallContextDefinition->setArguments([null]);
        $this->container->setDefinition('scheb_two_factor.firewall_context', $firewallContextDefinition);
    }

    /**
     * @param array<string,array<string,mixed>> $taggedServices
     */
    private function stubTaggedContainerService(array $taggedServices): void
    {
        foreach ($taggedServices as $id => $tags) {
            $definition = $this->container->register($id);

            foreach ($tags as $attributes) {
                $definition->addTag('scheb_two_factor.firewall_config', $attributes);
            }
        }
    }

    /**
     * @param array<string,Reference> $expectedTags
     */
    private function assertTwoFactorFirewallContextArgument(array $expectedTags): void
    {
        $configsArgument = $this->container->getDefinition('scheb_two_factor.firewall_context')->getArgument(0);
        $this->assertEquals($expectedTags, $configsArgument);
    }

    /**
     * @test
     */
    public function process_noTaggedServices_replaceArgumentWithEmptyArray(): void
    {
        $taggedServices = [];
        $this->stubTaggedContainerService($taggedServices);

        $this->compilerPass->process($this->container);

        $this->assertTwoFactorFirewallContextArgument([]);
    }

    /**
     * @test
     */
    public function process_taggedServices_replaceArgumentWithServiceList(): void
    {
        $taggedServices = [
            'serviceId' => [
                0 => ['firewall' => 'firewallName'],
            ],
        ];
        $this->stubTaggedContainerService($taggedServices);

        $this->compilerPass->process($this->container);

        $expectedResult = ['firewallName' => new Reference('serviceId')];
        $this->assertTwoFactorFirewallContextArgument($expectedResult);
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
        $this->compilerPass->process($this->container);
    }
}
