<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\DependencyInjection\Compiler;

use Scheb\TwoFactorBundle\DependencyInjection\Factory\Security\TwoFactorFactory;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Get all registered authentication providers and decorate them with AuthenticationProviderDecorator.
 */
class AuthenticatorDecoratorCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        foreach ($this->getFirewallsWithTwoFactorAuthentication($container) as $firewallName) {
            $this->decorateFirewallAuthenticators($container, $firewallName);
        }
    }

    private function getFirewallsWithTwoFactorAuthentication(ContainerBuilder $container): iterable
    {
        $taggedServices = $container->findTaggedServiceIds('scheb_two_factor.firewall_config');
        foreach ($taggedServices as $id => $attributes) {
            if (!isset($attributes[0]['firewall'])) {
                throw new InvalidArgumentException('Tag "scheb_two_factor.firewall_config" requires attribute "firewall" to be set.');
            }

            yield $attributes[0]['firewall'];
        }
    }

    private function decorateFirewallAuthenticators(ContainerBuilder $container, string $firewallName): void
    {
        foreach ($this->getAuthenticatorIds($container, $firewallName) as $authenticator) {
            // Ensure not to decorate the two-factor authenticator, otherwise we'll get an endless loop
            $authenticatorId = (string) $authenticator;
            if (!$this->isTwoFactorAuthenticator($authenticatorId)) {
                $this->decorateAuthenticator($container, $authenticatorId);
            }
        }
    }

    /**
     * @return Reference[]
     */
    private function getAuthenticatorIds(ContainerBuilder $container, string $firewallName): array
    {
        $authenticatorManagerId = 'security.authenticator.manager.'.$firewallName;
        if (!$container->hasDefinition($authenticatorManagerId)) {
            return [];
        }

        return $container->getDefinition($authenticatorManagerId)->getArgument(0);
    }

    private function decorateAuthenticator(ContainerBuilder $container, string $authenticatorId): void
    {
        $decoratedServiceId = $authenticatorId.'.two_factor_decorator';
        $container
            ->setDefinition($decoratedServiceId, new ChildDefinition('scheb_two_factor.security.authenticator.decorator'))
            ->setDecoratedService($authenticatorId)
            ->replaceArgument(0, new Reference($decoratedServiceId.'.inner'));
    }

    private function isTwoFactorAuthenticator(string $authenticatorId): bool
    {
        return false !== strpos($authenticatorId, TwoFactorFactory::AUTHENTICATOR_ID_PREFIX);
    }
}
