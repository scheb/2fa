<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Injects a listener into the firewall to handle access control during the "2fa in progress" phase.
 *
 * Compatibility for Symfony <= 5.1, from Symfony 5.2 on the bundle uses FirewallListenerFactoryInterface
 * to inject its TwoFactorAccessListener.
 *
 * @final
 */
class AccessListenerCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $taggedServices = $container->findTaggedServiceIds('scheb_two_factor.access_listener');
        foreach ($taggedServices as $id => $attributes) {
            if (!isset($attributes[0]['firewall'])) {
                throw new InvalidArgumentException('Tag "scheb_two_factor.access_listener" requires attribute "firewall" to be set.');
            }

            $firewallContextId = 'security.firewall.map.context.'.$attributes[0]['firewall'];
            $firewallContextDefinition = $container->getDefinition($firewallContextId);
            $listenersIterator = $firewallContextDefinition->getArgument(0);
            if (!($listenersIterator instanceof IteratorArgument)) {
                throw new InvalidArgumentException(sprintf('Cannot inject access listener, argument 0 of "%s" must be instance of IteratorArgument.', $firewallContextId));
            }

            $listeners = $listenersIterator->getValues();
            $listeners[] = new Reference($id);
            $listenersIterator->setValues($listeners);
        }
    }
}
