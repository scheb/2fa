<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Decorates all remember-me services instances so that the remember-me cookie doesn't leak when two-factor
 * authentication is required.
 */
class RememberMeServicesDecoratorCompilerPass implements CompilerPassInterface
{
    private const REMEMBER_ME_LISTENER_ID_PREFIX = 'security.authentication.listener.rememberme.';

    public function process(ContainerBuilder $container)
    {
        // Find all remember-me listener definitions
        $prefixLength = strlen(self::REMEMBER_ME_LISTENER_ID_PREFIX);
        foreach ($container->getDefinitions() as $definitionId => $definition) {
            if (substr($definitionId, 0, $prefixLength) === self::REMEMBER_ME_LISTENER_ID_PREFIX) {
                $this->decorateRememberMeServices($container, $definition);
            }
        }
    }

    private function decorateRememberMeServices(ContainerBuilder $container, Definition $authListenerDefinition): void
    {
        // Get the remember-me services from the listener and decorate it
        $rememberMeServicesId = (string) $authListenerDefinition->getArgument(1);
        if ($rememberMeServicesId) {
            $decoratedServiceId = $rememberMeServicesId.'.two_factor_decorator';
            $container
                ->setDefinition($decoratedServiceId, new ChildDefinition('scheb_two_factor.security.rememberme_services_decorator'))
                ->setDecoratedService($rememberMeServicesId)
                ->replaceArgument(0, new Reference($decoratedServiceId.'.inner'));
        }
    }
}
