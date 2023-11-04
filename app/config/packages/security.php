<?php

declare(strict_types=1);

use App\Entity\User;
use App\Kernel;
use Symfony\Component\DependencyInjection\ContainerBuilder;

$config = [
    'enable_authenticator_manager' => true,
    'providers' => [
        'our_db_provider' => [
            'entity' => [
                'class' => User::class,
                'property' => 'username',
            ],
        ],
    ],
    'firewalls' => [
        'dev' => [
            'pattern' => '^/(_(profiler|wdt)|css|images|js)/',
            'security' => false,
        ],
        'main' => [
            'lazy' => true,
            'pattern' => '^/',
            'provider' => 'our_db_provider',
            'login_throttling' => [
                'max_attempts' => 3,
                'interval' => '5 minutes',
            ],
            'form_login' => [
                'login_path' => '_security_login',
                'check_path' => '_security_login',
                'use_referer' => true,
            ],
            'logout' => [
                'path' => '_security_logout',
                'target' => 'home',
            ],
            'two-factor' => [
                'auth_form_path' => '2fa_login',
                'check_path' => '2fa_login_check',
                'auth_code_parameter_name' => '_auth_code',
                'trusted_parameter_name' => '_trusted',
                'multi_factor' => true,
                'provider' => 'our_db_provider',
                'prepare_on_login' => true,
                'prepare_on_access_denied' => true,
                'enable_csrf' => true,
            ],
            'remember_me' => [
                'secret' => '%kernel.secret%',
                'lifetime' => 604800,
                'path' => '/',
            ],
        ],
    ],
    'access_control' => [
        ['path' => '^/login', 'role' => 'PUBLIC_ACCESS'],
        ['path' => '^/alwaysAccessible', 'role' => 'PUBLIC_ACCESS'],
        ['path' => '^/2fa', 'role' => 'IS_AUTHENTICATED_2FA_IN_PROGRESS'],
        ['path' => '^/members', 'role' => ['ROLE_USER', 'ROLE_ADMIN']],
    ],
];

// Symfony 5.4
if (Kernel::VERSION_ID < 60000) {
    $config = array_replace_recursive($config, [
        'encoders' => [
            User::class => ['algorithm' => 'sha1'],
        ],
    ]);
} else {
    $config = array_replace_recursive($config, [
        'password_hashers' => [
            User::class => ['algorithm' => 'sha1'],
        ],
    ]);
}

/** @var ContainerBuilder $container */
$container->loadFromExtension('security', $config);
