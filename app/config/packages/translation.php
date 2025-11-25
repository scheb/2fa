<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('framework', [
        'default_locale' => '%locale%',
        'translator' => [
            'paths' => null,
            'fallbacks' => [
                '%locale%',
            ],
        ],
    ]);
};
