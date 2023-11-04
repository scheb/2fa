Upgrading
=========

Here's an overview if you have to do any work when upgrading.

6.x to 7.x
----------

Nothing to be done. Upgrade and enjoy :)

5.x to 6.x
----------

The bundle now **requires the authenticator-based security system to be used**. Please make sure
`enable_authenticator_manager` is enabled in the security configuration. If you're using Symfony 6.0.2 or newer, it is no
longer necessary to declare `enable_authenticator_manager: true` in the configuration, as it's enabled per default.

The default value of `security_tokens`, used when no specific configuration is given, has changed to:

- `Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken`
- `Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken`

When a two-factor provider is enabled a secret code/TOTP configuration has to be returned, otherwise a
`TwoFactorProviderLogicException` will be thrown. Before, the two-factor provider was gracefully skipped.

If you use a custom implementation of `Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface`,
method `getProviderKey()` was removed, please implement `getFirewallName()` instead.

`Scheb\TwoFactorBundle\Security\Http\Authenticator\Passport\TwoFactorPassport` was removed, use
`Symfony\Component\Security\Http\Authenticator\Passport\Passport` with a
`Scheb\TwoFactorBundle\Security\Http\Authenticator\Passport\Credentials\TwoFactorCodeCredentials` instead.

The internal interface `Scheb\TwoFactorBundle\Security\TwoFactor\Handler\AuthenticationHandlerInterface` was removed. If
you used it nevertheless, please migrate to an implementation based on
`Scheb\TwoFactorBundle\Security\TwoFactor\Condition\TwoFactorConditionInterface`. See more about
[custom conditions for two-factor authentication](https://symfony.com/bundles/SchebTwoFactorBundle/6.x/custom_conditions.html).

The bundle has previously recommended the controller syntax with a single colon
`_controller: "scheb_two_factor.form_controller:form"`. Under Symfony 6, you have to use the syntax with two colons
`_controller: "scheb_two_factor.form_controller::form"` (which is also compatible with Symfony 5.4).

### Authentication Context

The passport has been added to the `AuthenticationContext` object, therefore a new getter method was added to
`Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface`:

```php
public function getPassport(): Passport
```

The signature in `Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextFactoryInterface` was adjusted,
so if you use a custom factory implementation, please adjust it accordingly:

```php
public function create(Request $request, TokenInterface $token, Passport $passport, string $firewallName): AuthenticationContextInterface
```

In addition, the constructor of the basic `Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContext` class was
extended with a new parameter for the passport, so if you're extending from this class, please adjust your constructor.

### Interface Changes

In `Scheb\TwoFactorBundle\Model\PersisterInterface` the `$user` is now typed as `object`:

Before:

```php
public function persist($user): void;
```

After:

```php
public function persist(object $user): void;
```

In `Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderInterface` the `$user` is now typed as `object`:

Before:

```php
public function prepareAuthentication($user): void;
public function validateAuthenticationCode($user, string $authenticationCode): bool;
```

After:

```php
public function prepareAuthentication(object $user): void;
public function validateAuthenticationCode(object $user, string $authenticationCode): bool;
```

### Constant Changes

Internal constant `Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface::ATTRIBUTE_NAME_REMEMBER_ME_COOKIE`
has been removed.

`Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderPreparationListener::LISTENER_PRIORITY` was removed,
use `AUTHENTICATION_SUCCESS_LISTENER_PRIORITY` instead.

In `Scheb\TwoFactorBundle\DependencyInjection\Factory\Security\TwoFactorFactory` the following constants related to the
old security system have been removed:

- `PROVIDER_ID_PREFIX`
- `LISTENER_ID_PREFIX`
- `PROVIDER_DEFINITION_ID`
- `LISTENER_DEFINITION_ID`

### `scheb/2fa-backup-code` Package

In `Scheb\TwoFactorBundle\Security\TwoFactor\Backup\BackupCodeManagerInterface` the `$user` is now typed as `object`:

Before:

```php
public function isBackupCode($user, string $code): bool;
public function invalidateBackupCode($user, string $code): void;
```

After:

```php
public function isBackupCode(object $user, string $code): bool;
public function invalidateBackupCode(object $user, string $code): void;
```

### `scheb/2fa-email` Package

Out-of-the-box support for `symfony/swiftmailer-bundle` was removed, respectively
`Scheb\TwoFactorBundle\Mailer\SwiftAuthCodeMailer` was removed. Please migrate to `symfony/mailer` or use a
[custom mailer implementation](https://symfony.com/bundles/SchebTwoFactorBundle/6.x/providers/email.html#custom-mailer).

Signature of `Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface::getEmailAuthCode()` has changed to be nullable,
please update your implementation accordingly.

Before:

```php
public function getEmailAuthCode(): string;
```

After:

```php
public function getEmailAuthCode(): ?string;
```

### `scheb/2fa-trusted-device` Package

In `Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\TrustedDeviceManagerInterface` the `$user` is now typed as `object`:

Before:

```php
public function canSetTrustedDevice($user, Request $request, string $firewallName): bool;
public function addTrustedDevice($user, string $firewallName): void;
public function isTrustedDevice($user, string $firewallName): bool;
```

After:

```php
public function canSetTrustedDevice(object $user, Request $request, string $firewallName): bool;
public function addTrustedDevice(object $user, string $firewallName): void;
public function isTrustedDevice(object $user, string $firewallName): bool;
```

### `scheb/2fa-qr-code` Package

The package `scheb/2fa-qr-code` was discontinued. Please migrate to get QR code content from service
`scheb_two_factor.security.google_authenticator` or `scheb_two_factor.security.totp_authenticator` and use the
`endroid/qr-code` package (or any alternative) to render an QR code image.

An example how to render the QR code with `endroid/qr-code` version 4 can be found
[in the test application](https://github.com/scheb/2fa/blob/6.x/app/src/Controller/QrCodeController.php).

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
