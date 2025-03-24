<?php

declare(strict_types=1);

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Scheb\TwoFactorBundle\Security\Http\EventListener\TrustedDeviceListener;
use Scheb\TwoFactorBundle\Security\TwoFactor\Condition\TrustedDeviceCondition;
use Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\JwtTokenEncoder;
use Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\NullTrustedDeviceManager;
use Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\TrustedCookieResponseListener;
use Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\TrustedDeviceManager;
use Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\TrustedDeviceTokenEncoder;
use Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\TrustedDeviceTokenStorage;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('scheb_two_factor.trusted_jwt_encoder.configuration.algorithm', Sha256::class)

        ->set('scheb_two_factor.trusted_jwt_encoder.configuration.key', InMemory::class)
            ->factory([InMemory::class, 'plainText'])
            ->args(['%kernel.secret%'])

        ->set('scheb_two_factor.trusted_jwt_encoder.configuration', Configuration::class)
            ->factory([Configuration::class, 'forSymmetricSigner'])
            ->args([
                service('scheb_two_factor.trusted_jwt_encoder.configuration.algorithm'),
                service('scheb_two_factor.trusted_jwt_encoder.configuration.key'),
            ])

        ->set('scheb_two_factor.trusted_jwt_encoder', JwtTokenEncoder::class)
            ->args([service('scheb_two_factor.trusted_jwt_encoder.configuration')])

        ->set('scheb_two_factor.trusted_token_encoder', TrustedDeviceTokenEncoder::class)
            ->args([
                service('scheb_two_factor.trusted_jwt_encoder'),
                '%scheb_two_factor.trusted_device.lifetime%',
            ])

        ->set('scheb_two_factor.trusted_token_storage', TrustedDeviceTokenStorage::class)
            ->tag('kernel.reset', ['method' => 'reset'])
            ->lazy(true)
            ->args([
                service('request_stack'),
                service('scheb_two_factor.trusted_token_encoder'),
                '%scheb_two_factor.trusted_device.cookie_name%',
            ])

        ->set('scheb_two_factor.trusted_device_condition', TrustedDeviceCondition::class)
            ->lazy(true)
            ->args([
                service('scheb_two_factor.trusted_device_manager'),
                '%scheb_two_factor.trusted_device.extend_lifetime%',
            ])

        ->set('scheb_two_factor.trusted_cookie_response_listener', TrustedCookieResponseListener::class)
            ->tag('kernel.event_subscriber')
            ->lazy(true)
            ->args([
                service('scheb_two_factor.trusted_token_storage'),
                '%scheb_two_factor.trusted_device.lifetime%',
                '%scheb_two_factor.trusted_device.cookie_name%',
                '%scheb_two_factor.trusted_device.cookie_secure%',
                '%scheb_two_factor.trusted_device.cookie_same_site%',
                '%scheb_two_factor.trusted_device.cookie_path%',
                '%scheb_two_factor.trusted_device.cookie_domain%',
            ])

        ->set('scheb_two_factor.security.listener.trusted_device', TrustedDeviceListener::class)
            ->tag('kernel.event_subscriber')
            ->args([service('scheb_two_factor.trusted_device_manager')])

        ->set('scheb_two_factor.default_trusted_device_manager', TrustedDeviceManager::class)
            ->args([service('scheb_two_factor.trusted_token_storage')])

        ->set('scheb_two_factor.null_trusted_device_manager', NullTrustedDeviceManager::class);
};
