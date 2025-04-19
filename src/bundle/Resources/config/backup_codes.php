<?php

declare(strict_types=1);

use Scheb\TwoFactorBundle\Security\Http\EventListener\CheckBackupCodeListener;
use Scheb\TwoFactorBundle\Security\TwoFactor\Backup\BackupCodeManager;
use Scheb\TwoFactorBundle\Security\TwoFactor\Backup\NullBackupCodeManager;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('scheb_two_factor.default_backup_code_manager', BackupCodeManager::class)
            ->args([service('scheb_two_factor.persister')])

        ->set('scheb_two_factor.null_backup_code_manager', NullBackupCodeManager::class)

        ->set('scheb_two_factor.security.listener.check_backup_code', CheckBackupCodeListener::class)
            ->tag('kernel.event_subscriber')
            ->args([
                service('scheb_two_factor.provider_preparation_recorder'),
                service('scheb_two_factor.backup_code_manager'),
                service('event_dispatcher'),
            ]);
};
