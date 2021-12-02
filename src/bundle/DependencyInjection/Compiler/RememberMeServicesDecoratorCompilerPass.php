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
 *
 * Compatibility for Symfony's old security. When the authenticator-based security system is enabled, a different
 * approach is used to handle the remember-me feature.
 *
 * @final
 */
class RememberMeServicesDecoratorCompilerPass implements CompilerPassInterface
{
    private const REMEMBER_ME_AUTHENTICATION_LISTENER_ID_PREFIX = 'security.authentication.listener.rememberme.';

    public function process(ContainerBuilder $container): void
    {
        // Skip this compiler pass when authenticator security is enabled. When the authenticator-based security system
        // is enabled, a different approach is used to handle the remember-me feature.
        if ($container->hasDefinition('security.authenticator.manager')) {
            return;
        }

        // Find all remember-me listener definitions
        foreach ($container->getDefinitions() as $definitionId => $definition) {
            // Classic security system
            /** @psalm-suppress RedundantCastGivenDocblockType */
            if (0 === strpos((string) $definitionId, self::REMEMBER_ME_AUTHENTICATION_LISTENER_ID_PREFIX)) {
                $this->decorateRememberMeServices($container, $definition, 1);
            }
        }
    }

    private function decorateRememberMeServices(ContainerBuilder $container, Definition $authListenerDefinition, int $argument): void
    {
        // Get the remember-me services from the listener and decorate it
        $rememberMeServicesId = (string) $authListenerDefinition->getArgument($argument);
        if ($rememberMeServicesId) {
            $decoratedServiceId = $rememberMeServicesId.'.two_factor_decorator';
            $container
                ->setDefinition($decoratedServiceId, new ChildDefinition('scheb_two_factor.security.rememberme_services_decorator'))
                ->setDecoratedService($rememberMeServicesId)
                ->replaceArgument(0, new Reference($decoratedServiceId.'.inner'));
        }
    }
}
