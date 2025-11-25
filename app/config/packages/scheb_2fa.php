<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('scheb_two_factor', [
        'trusted_device' => [
            'enabled' => true,
            'lifetime' => 5184000,
            'extend_lifetime' => true,
            'key' => 'cc21ed8d4b2a28a1f14028428fd3b5e5scheb',
            'cookie_name' => 'trusted_device',
            'cookie_secure' => false,
            'cookie_same_site' => 'lax',
        ],
        'backup_codes' => [
            'enabled' => true,
        ],
        'email' => [
            'enabled' => true,
            'sender_email' => 'me@example.com',
            'sender_name' => 'John Doe',
            'digits' => 4,
            'template' => 'security/2fa.html.twig',
        ],
        'google' => [
            'enabled' => true,
            'server_name' => 'Server Name',
            'issuer' => 'Issuer Name',
            'leeway' => 15,
            'template' => 'security/2fa.html.twig',
        ],
        'totp' => [
            'enabled' => true,
            'server_name' => 'Server Name',
            'issuer' => 'Issuer Name',
            'leeway' => 15,
            'parameters' => [
                'image' => 'https://my-service/img/logo.png',
            ],
            'template' => 'security/2fa.html.twig',
        ],
        'persister' => 'scheb_two_factor.persister.doctrine',
        'model_manager_name' => null,
        'security_tokens' => [
            UsernamePasswordToken::class,
        ],
        'ip_whitelist' => [
            '127.0.0.2',
        ],
    ]);
};
