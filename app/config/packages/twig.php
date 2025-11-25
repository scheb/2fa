<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('twig', [
        'paths' => [
            '%kernel.project_dir%/templates',
        ],
        'debug' => '%kernel.debug%',
        'strict_variables' => '%kernel.debug%',
    ]);
};
