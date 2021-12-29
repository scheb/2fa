<?php

declare(strict_types=1);

use Scheb\TwoFactorBundle\Model\Persister\DoctrinePersister;
use Scheb\TwoFactorBundle\Model\Persister\DoctrinePersisterFactory;
use Scheb\TwoFactorBundle\Model\PersisterInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('scheb_two_factor.persister_factory.doctrine', DoctrinePersisterFactory::class)
            ->args([
                service('doctrine')->nullOnInvalid(),
                '%scheb_two_factor.model_manager_name%',
            ])

        ->set('scheb_two_factor.persister.doctrine', DoctrinePersister::class)
            ->lazy(true)
            ->factory([service('scheb_two_factor.persister_factory.doctrine'), 'getPersister'])

        ->alias(PersisterInterface::class, 'scheb_two_factor.persister');
};
