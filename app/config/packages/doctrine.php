<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();

    $parameters->set('env(DATABASE_URL)', '');

    $containerConfigurator->extension('doctrine', [
        'dbal' => [
            'url' => '%env(resolve:DATABASE_URL)%',
        ],
        'orm' => [
            'naming_strategy' => 'doctrine.orm.naming_strategy.underscore',
            'auto_mapping' => true,
            'mappings' => [
                'App' => [
                    'is_bundle' => false,
                    'type' => 'attribute',
                    'dir' => '%kernel.project_dir%/src/Entity',
                    'prefix' => 'App\Entity',
                    'alias' => 'App',
                ],
            ],
        ],
    ]);
};
