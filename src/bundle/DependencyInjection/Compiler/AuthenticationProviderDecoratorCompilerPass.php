<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\DependencyInjection\Compiler;

use Scheb\TwoFactorBundle\DependencyInjection\Factory\Security\TwoFactorFactory;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\OutOfBoundsException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Get all registered authentication providers and decorate them with AuthenticationProviderDecorator.
 *
 * @final
 */
class AuthenticationProviderDecoratorCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        foreach ($this->getAuthenticationProviderIds($container) as $authenticationProvider) {
            // Ensure not to decorate the two-factor authentication provider, otherwise we'll get an endless loop
            $authenticationProviderId = (string) $authenticationProvider;
            if (!$this->isTwoFactorProvider($authenticationProviderId)) {
                $this->decorateAuthenticationProvider($container, $authenticationProviderId);
            }
        }
    }

    /**
     * @return Reference[]
     */
    private function getAuthenticationProviderIds(ContainerBuilder $container): array
    {
        if (!$container->hasDefinition('security.authentication.manager')) {
            return [];
        }

        try {
            return $container->getDefinition('security.authentication.manager')->getArgument(0)->getValues();
        } catch (OutOfBoundsException $e) {
            // Authentication providers are not set, so the authenticator system is used
            return [];
        }
    }

    private function decorateAuthenticationProvider(ContainerBuilder $container, string $providerId): void
    {
        $decoratedServiceId = $providerId.'.two_factor_decorator';
        $container
            ->setDefinition($decoratedServiceId, new ChildDefinition('scheb_two_factor.security.authentication.provider.decorator'))
            ->setDecoratedService($providerId)
            ->replaceArgument(0, new Reference($decoratedServiceId.'.inner'));
    }

    private function isTwoFactorProvider(string $authenticationProviderId): bool
    {
        return false !== strpos($authenticationProviderId, TwoFactorFactory::PROVIDER_ID_PREFIX);
    }
}
