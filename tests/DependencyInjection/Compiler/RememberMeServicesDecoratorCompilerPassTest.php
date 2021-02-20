<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\DependencyInjection\Compiler;

use Scheb\TwoFactorBundle\DependencyInjection\Compiler\AuthenticationProviderDecoratorCompilerPass;
use Scheb\TwoFactorBundle\DependencyInjection\Compiler\RememberMeServicesDecoratorCompilerPass;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Security\Http\Firewall\RememberMeListener;
use Symfony\Component\Security\Http\RememberMe\AbstractRememberMeServices;

class RememberMeServicesDecoratorCompilerPassTest extends TestCase
{
    /**
     * @var ContainerBuilder
     */
    private $container;

    /**
     * @var AuthenticationProviderDecoratorCompilerPass
     */
    private $compilerPass;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->compilerPass = new RememberMeServicesDecoratorCompilerPass();
    }

    private function stubRememberMeListenersWithServices(array $firewalls): void
    {
        foreach ($firewalls as $firewallName) {
            $rememberMeServicesId = $this->createRememberMeServicesDefinition($firewallName);
            $this->createListenerDefinition('security.authentication.listener.rememberme.'.$firewallName, 1, $rememberMeServicesId);
        }
    }

    private function createRememberMeServicesDefinition(string $firewallName): string
    {
        $rememberMeServicesId = 'rememberme_services.'.$firewallName;
        $rememberMeServicesDefinition = new Definition(AbstractRememberMeServices::class);
        $this->container->setDefinition($rememberMeServicesId, $rememberMeServicesDefinition);

        return $rememberMeServicesId;
    }

    private function createListenerDefinition(string $listenerId, int $argument, string $rememberMeServicesId): void
    {
        $listenerDefinition = new Definition(RememberMeListener::class);
        $listenerDefinition->setArgument($argument, new Reference($rememberMeServicesId));
        $this->container->setDefinition($listenerId, $listenerDefinition);
    }

    private function assertContainerHasDecoratedProvider(string $rememberMeServicesId): void
    {
        $expectedDecoratorId = $rememberMeServicesId.'.two_factor_decorator';
        $expectedDecoratedId = $expectedDecoratorId.'.inner';

        $this->assertTrue($this->container->hasDefinition($expectedDecoratorId), 'Must have service "'.$expectedDecoratorId.'" defined.');

        $decoratorDefinition = $this->container->getDefinition($expectedDecoratorId);
        $decoratedServiceReference = $decoratorDefinition->getArgument(0);
        $this->assertEquals($expectedDecoratedId, (string) $decoratedServiceReference);
        $this->assertEquals($rememberMeServicesId, $decoratorDefinition->getDecoratedService()[0]);
    }

    /**
     * @test
     */
    public function process_multipleRememberMeServicesWithClassicSecurity_decorateAll(): void
    {
        $this->stubRememberMeListenersWithServices([
            'firewall1',
            'firewall2',
        ]);

        $this->compilerPass->process($this->container);

        $this->assertContainerHasDecoratedProvider('rememberme_services.firewall1');
        $this->assertContainerHasDecoratedProvider('rememberme_services.firewall2');
    }
}
