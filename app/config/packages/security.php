<?php

declare(strict_types=1);

use App\Entity\User;
use App\Kernel;
use Symfony\Component\DependencyInjection\ContainerBuilder;

$configVariant = getenv('TEST_CONFIG');
if (!\is_string($configVariant) || 0 === \strlen($configVariant)) {
    $configVariant = 'default';
}

$config = [
    'providers' => [
        'our_db_provider' => [
            'entity' => [
                'class' => User::class,
                'property' => 'username',
            ],
        ],
    ],
    'encoders' => [
        User::class => ['algorithm' => 'sha1'],
    ],
    'firewalls' => [
        'dev' => [
            'pattern' => '^/(_(profiler|wdt)|css|images|js)/',
            'security' => false,
        ],
        'main' => [
            'pattern' => '^/',
            'provider' => 'our_db_provider',
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
        ['path' => '^/login', 'role' => 'IS_AUTHENTICATED_ANONYMOUSLY'],
        ['path' => '^/alwaysAccessible', 'role' => 'IS_AUTHENTICATED_ANONYMOUSLY'],
        ['path' => '^/2fa', 'role' => 'IS_AUTHENTICATED_2FA_IN_PROGRESS'],
        ['path' => '^/members', 'role' => ['ROLE_USER', 'ROLE_ADMIN']],
    ],
];

if ('authenticators' === $configVariant) {
    // AUTHENTICATORS config
    $config = array_replace_recursive($config, [
        'enable_authenticator_manager' => true,
        'firewalls' => [
            'main' => [
                'lazy' => true,
                'entry_point' => 'form_login', // Temporary workaround for symfony/symfony#39249
            ],
        ],
    ]);
} elseif ('default' === $configVariant) {
    // DEFAULT config
    // Make the firewall lazy and anonymous
    if (Kernel::VERSION_ID < 50100) {
        // Compatibility for Symfony < 5.1
        $config['firewalls']['main']['anonymous'] = 'lazy';
    } else {
        // Compatibility for Symfony >= 5.1
        $config['firewalls']['main']['lazy'] = true;
        $config['firewalls']['main']['anonymous'] = null;
    }
} else {
    throw new \LogicException(sprintf('Invalid config variant "%s" requested.', $configVariant));
}

/** @var ContainerBuilder $container */
$container->loadFromExtension('security', $config);
