<?php

declare(strict_types=1);

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routingConfigurator): void {
    $routingConfigurator->add('2fa_login', '/2fa')
        ->controller([
            'scheb_two_factor.form_controller',
            'form',
        ]);

    $routingConfigurator->add('2fa_login_check', '/2fa_check');
};
