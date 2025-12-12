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
        'globals' => [
            'VERSION_COMMIT_ID' => '%env(default::VERSION_COMMIT_ID)%',
            'VERSION_DATE' => '%env(default::VERSION_DATE)%',
        ],
    ]);
};
