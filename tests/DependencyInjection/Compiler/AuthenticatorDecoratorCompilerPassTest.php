<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\DependencyInjection\Compiler;

use Scheb\TwoFactorBundle\DependencyInjection\Compiler\AuthenticatorDecoratorCompilerPass;
use Scheb\TwoFactorBundle\DependencyInjection\Factory\Security\TwoFactorFactory;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;

class AuthenticatorDecoratorCompilerPassTest extends TestCase
{
    /**
     * @var ContainerBuilder
     */
    private $container;

    /**
     * @var AuthenticatorDecoratorCompilerPass
     */
    private $compilerPass;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->compilerPass = new AuthenticatorDecoratorCompilerPass();
    }

    private function stubAuthenticatorManagerWithAuthenticators(string $firewallName, array $authenticatorIds): void
    {
        $authenticatorReferences = [];
        foreach ($authenticatorIds as $authenticatorId) {
            $authenticatorDefinition = new Definition(AuthenticatorInterface::class);
            $this->container->setDefinition($authenticatorId, $authenticatorDefinition);
            $authenticatorReferences[] = new Reference($authenticatorId);
        }
        $authenticationManagerDefinition = new Definition(AuthenticationManagerInterface::class);
        $authenticationManagerDefinition->setArgument(0, $authenticatorReferences);
        $this->container->setDefinition('security.authenticator.manager.'.$firewallName, $authenticationManagerDefinition);
    }

    private function assertContainerHasDecoratedAuthenticator(string $authenticatorId): void
    {
        $expectedDecoratorId = $authenticatorId.'.two_factor_decorator';
        $expectedDecoratedId = $expectedDecoratorId.'.inner';

        $this->assertTrue($this->container->hasDefinition($expectedDecoratorId), 'Must have service "'.$expectedDecoratorId.'" defined.');

        $decoratorDefinition = $this->container->getDefinition($expectedDecoratorId);
        $decoratedServiceReference = $decoratorDefinition->getArgument(0);
        $this->assertEquals($expectedDecoratedId, (string) $decoratedServiceReference);
        $this->assertEquals($authenticatorId, $decoratorDefinition->getDecoratedService()[0]);
    }

    private function assertContainerNotHasDecoratedProvider(string $authenticatorId): void
    {
        $expectedDecoratorId = $authenticatorId.'.two_factor_decorator';
        $this->assertFalse($this->container->hasDefinition($expectedDecoratorId), 'Must not have service "'.$expectedDecoratorId.'" defined.');
    }

    private function stubTwoFactorFirewallConfig(string $firewallName): void
    {
        $definition = $this->container->register('firewall_config.'.$firewallName);
        $definition->addTag('scheb_two_factor.firewall_config', ['firewall' => $firewallName]);
    }

    /**
     * @test
     */
    public function process_hasMultipleAuthenticationProviders_decorateAll(): void
    {
        $this->stubTwoFactorFirewallConfig('main');

        $this->stubAuthenticatorManagerWithAuthenticators('main', [
            'security.authenticator.foo.main',
            'security.authenticator.bar.main',
            TwoFactorFactory::PROVIDER_ID_PREFIX.'.main', // This is the two-factor provider, must not be decorated
        ]);

        // The second firewall that doesn't have two-factor authentication
        $this->stubAuthenticatorManagerWithAuthenticators('other', [
            'security.authenticator.foo.other',
            'security.authenticator.bar.other',
        ]);

        $this->compilerPass->process($this->container);

        $this->assertContainerHasDecoratedAuthenticator('security.authenticator.foo.main');
        $this->assertContainerHasDecoratedAuthenticator('security.authenticator.bar.main');
        $this->assertContainerNotHasDecoratedProvider('security.authentication.provider.two_factor.main');

        $this->assertContainerNotHasDecoratedProvider('security.authenticator.foo.other');
        $this->assertContainerNotHasDecoratedProvider('security.authenticator.bar.other');
    }
}
