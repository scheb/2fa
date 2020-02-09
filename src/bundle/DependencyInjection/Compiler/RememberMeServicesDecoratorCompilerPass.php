<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2020 Christian Scheb
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

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

    /**
     * @return void
     */
    public function process(ContainerBuilder $container)
    {
        // Find all remember-me listener definitions
        $prefixLength = \mb_strlen(self::REMEMBER_ME_LISTENER_ID_PREFIX);
        foreach ($container->getDefinitions() as $definitionId => $definition) {
            if (0 === mb_strpos($definitionId, self::REMEMBER_ME_LISTENER_ID_PREFIX)) {
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
