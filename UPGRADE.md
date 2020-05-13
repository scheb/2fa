Upgrading
=========

Here's an overview if you have to do any work when upgrading.

## 4.x to 5.x

### Packages

To limit the number of dependencies you're pulling into your project, the bundle was split into multiple packages, which
are available under the new name of `scheb/2fa`. You need to migrate from `scheb/two-factor-bundle` (version 4) to
`scheb/2fa` (version 5).

Use these operations to upgrade from `scheb/two-factor-bundle` to `scheb/2fa-*` packages:

```bash
# Rename configuration files. This is actually not required, but good to do for consistency. Also, Symfony Flex doesn't
# remove the config files when the "composer remove" command is later executed.
mv config/packages/scheb_two_factor.yaml config/packages/scheb_2fa.yaml
mv config/routes/scheb_two_factor.yaml config/routes/scheb_2fa.yaml  # Might not exist, then ignore.

# Switch composer packages
composer remove scheb/two-factor-bundle --no-scripts
composer require scheb/2fa-bundle

# Then add the extra packages, depending on what bundle features you're using in your application:
composer require scheb/2fa-backup-code            # Add backup code feature
composer require scheb/2fa-trusted-devices        # Add trusted devices feature
composer require scheb/2fa-totp                   # Add two-factor authentication using TOTP
composer require scheb/2fa-google-authenticator   # Add two-factor authentication with Google Authenticator
composer require scheb/2fa-email                  # Add two-factor authentication using email
```

### Configuration

Guard-based authentication has become the preferred way of building a custom authentication provider. Therefore,
`Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken` is now configured per default in `security_tokens`
as a token to triggers two-factor authentication. If you don't want to have it automatically configured, please set
`security_tokens` in your bundle configuration.

The `check_path` now accepts the two-factor authentication code only with a POST request. This can be changed by setting
the `post_only: false` option on the firewall.

```yaml
# config/packages/security.yaml
security:
    firewalls:
        yourFirewallName:
            # ...
            two_factor:
                post_only: false
```

### Interfaces

`Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\TrustedDeviceManagerInterface` was extended with a new method
`canSetTrustedDevice` which allows the application to influence when a device can be flagged as a "trusted device". If
you have implemented your own TrustedDeviceManager, please add this method. Just return `true` to get the same behaviour
as before.

The constructor of `Scheb\TwoFactorBundle\Controller\FormController` now takes an instance of
`Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\TrustedDeviceManagerInterface` as the fifth argument. If you have
extended the controller, please update your service definition accordingly.

`Scheb\TwoFactorBundle\Security\Authentication\Exception\InvalidTwoFactorCodeException::setMessageKey` has been removed.
