<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\DependencyInjection\Factory\Security;

use Scheb\TwoFactorBundle\DependencyInjection\Factory\Security\TwoFactorFactory;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Helper class to process config.
 */
class TestableFactoryConfiguration implements ConfigurationInterface
{
    /**
     * @var TwoFactorFactory
     */
    private $factory;

    public function __construct(TwoFactorFactory $factory)
    {
        $this->factory = $factory;
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(TwoFactorFactory::AUTHENTICATION_PROVIDER_KEY);
        $rootNode = $treeBuilder->getRootNode();
        $this->factory->addConfiguration($rootNode);

        return $treeBuilder;
    }
}
