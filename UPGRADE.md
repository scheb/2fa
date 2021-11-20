Upgrading
=========

Here's an overview if you have to do any work when upgrading.

5.x to 6.x
----------

If you use a custom implementation of `Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface`,
method `getProviderKey()` was removed, please implement `getFirewallName()` instead.

Internal constant `Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface::ATTRIBUTE_NAME_REMEMBER_ME_COOKIE`
has been removed.

`Scheb\TwoFactorBundle\Security\Http\Authenticator\Passport\TwoFactorPassport` was removed, using
`Symfony\Component\Security\Http\Authenticator\Passport\Passport` with a
`Scheb\TwoFactorBundle\Security\Http\Authenticator\Passport\Credentials\TwoFactorCodeCredentials` instead.

Out-of-the-box support for `symfony/swiftmailer-bundle` was removed, respectively
`Scheb\TwoFactorBundle\Mailer\SwiftAuthCodeMailer` was removed. Please migrate to `symfony/mailer` or use a
[custom mailer implementation](https://symfony.com/bundles/SchebTwoFactorBundle/6.x/providers/email.html#custom-mailer).

4.x to 5.x
----------

### Packages

To limit the number of dependencies you're pulling into your project, the bundle was split into multiple packages, which
are available under the new name of `scheb/2fa`. You need to migrate from `scheb/two-factor-bundle` (version 4) to
`scheb/2fa` (version 5).

Use these operations to upgrade from `scheb/two-factor-bundle` to `scheb/2fa-*` packages:

```bash
# Rename configuration files. This is actually not required, but good to do for consistency. Also, Symfony
# Flex doesn't remove the config files when the "composer remove" command is later executed.
mv config/packages/scheb_two_factor.yaml config/packages/scheb_2fa.yaml
mv config/routes/scheb_two_factor.yaml config/routes/scheb_2fa.yaml  # Might not exist, then ignore.

# Switch composer packages
composer remove scheb/two-factor-bundle --no-scripts
composer require scheb/2fa-bundle

# Then add the extra packages, depending on what bundle features you're using in your application:
composer require scheb/2fa-backup-code            # Add backup code feature
composer require scheb/2fa-trusted-device         # Add trusted devices feature
composer require scheb/2fa-totp                   # Add two-factor authentication using TOTP
composer require scheb/2fa-google-authenticator   # Add two-factor authentication with Google Authenticator
composer require scheb/2fa-email                  # Add two-factor authentication using email
```

### Configuration

#### Default Security Token

Guard-based authentication has become the preferred way of building a custom authentication provider. Furthermore,
Symfony 5.1 introduced the new "authenticator"-based system, which is intended to become the preferred way in the
future. Therefore, the security token

- `Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken`
- `Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken`
- `Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken`

are now configured per default in `security_tokens` as token to triggers two-factor authentication. If you don't want to
have these automatically configured, please set `security_tokens` in your bundle configuration.

#### Check Path POST Only

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

#### CSRF

The option `csrf_token_generator` in two-factor firewall configuration was removed. Please use `enable_csrf: true`
instead.

Before:

```yaml
# config/packages/security.yaml
security:
    firewalls:
        yourFirewallName:
            # ...
            two_factor:
                csrf_token_generator: security.csrf.token_manager
```

After:

```yaml
# config/packages/security.yaml
security:
    firewalls:
        yourFirewallName:
            # ...
            two_factor:
                enable_csrf: true
```

If you have used a CSRF token manager other than `security.csrf.token_manager` before, please overwrite service
definition `scheb_two_factor.csrf_token_manager` with the service to use.

### Interfaces

#### TwoFactorTokenInterface

`Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface` was extended with new methods to store the
preparation state of two-factor authentication providers. Methods `isTwoFactorProviderPrepared` and
`setTwoFactorProviderPrepared` have been added. Furthermore, the method `createWithCredentials` was added to recreate
an identical token with credentials. See `Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorToken` for a
reference implementation.

#### TwoFactorTokenFactoryInterface

Related to this change, the `credentials` argument was removed from
`Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenFactoryInterface::create`. If you're using your own
token factory, please update your code.

#### TrustedDeviceManagerInterface

`Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\TrustedDeviceManagerInterface` was extended with a new method
`canSetTrustedDevice`, which allows the application to influence under which conditions a device can be flagged
"trusted" If you have implemented your own TrustedDeviceManager, please add this method. Return `true` to get the same
behaviour as before.

#### Other

The constructor of `Scheb\TwoFactorBundle\Controller\FormController` now takes an instance of
`Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\TrustedDeviceManagerInterface` as the fifth argument. If you have
extended the controller to customize it, please update your service definition accordingly.

`Scheb\TwoFactorBundle\Security\Authentication\Exception\InvalidTwoFactorCodeException::setMessageKey` has been removed.

`Scheb\TwoFactorBundle\Mailer\AuthCodeMailer` was renamed to `Scheb\TwoFactorBundle\Mailer\SwiftAuthCodeMailer`.

### Behavioural

The preparation of two-factor providers now executes on the `kernel.response` event to make sure the session state is
written before the response is sent back to the user (was `kernel.finish_request` before).
