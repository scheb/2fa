Installation
============

## Prerequisites

If you're using anything other than Doctrine ORM to manage the user entity you will have to implement a
[persister service](persister.md).

## Installation

### Step 1: Install with Composer

The bundle is organized into sub-repositories, so you can choose the exact feature set you need and keep installed
dependencies to a minimum.

Install at least the bundle via Composer:
```
composer require scheb/2fa-bundle
```

Optionally, install additional packages to extend the feature set for your needs:
```
composer require scheb/2fa-backup-code        # Add backup code feature
composer require scheb/2fa-trusted-devices    # Add trusted devices feature
composer require scheb/totp                   # Add two-factor authentication using TOTP
composer require scheb/google-authenticator   # Add two-factor authentication with Google Authenticator
composer require scheb/email                  # Add two-factor authentication using email
```

Alternatively, you can install all packages at once:
```bash
composer require scheb/2fa
```

### Step 2: Enable the bundle

Enable this bundle in your `config/bundles.php`:

```php
return [
    // ...
    Scheb\TwoFactorBundle\SchebTwoFactorBundle::class => ['all' => true],
];
```

### Step 3: Define routes

In `config/routes.yaml` add a route for the two-factor authentication form and another one for checking the
authentication code. *Please note:* The routes must be located within the path of the firewall, which should use
two-factor authentication.

```yaml
# config/routes.yaml
2fa_login:
    path: /2fa
    defaults:
        _controller: "scheb_two_factor.form_controller:form"

2fa_login_check:
    path: /2fa_check
```

### Step 4: Configure the firewall

Enable two-factor authentication **per firewall** and configure `access_control` for the 2fa routes:

```yaml
# config/packages/security.yaml
security:
    firewalls:
        main:
            two_factor:
                auth_form_path: 2fa_login    # The route name you have used in the routes.yaml
                check_path: 2fa_login_check  # The route name you have used in the routes.yaml

    # The path patterns shown here have to be updated according to your routes, if you're going with something custom
    access_control:
        # This makes the logout route available during two-factor authentication, allows the user to cancel
        - { path: ^/logout, role: IS_AUTHENTICATED_ANONYMOUSLY }
        # This ensures that the form can only be accessed when two-factor authentication is in progress
        - { path: ^/2fa, role: IS_AUTHENTICATED_2FA_IN_PROGRESS }
```

More per-firewall configuration options can be found in the [configuration reference](configuration.md).

### Step 5: Configure authentication tokens

Your firewall may offer different ways how to login. By default the bundle is only listening to the user-password
authentication (which uses the token class `Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken`)
and guard-based providers (which are using `Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken`).
If you want to support two-factor authentication with another login method, you have to register its token class in the
`scheb_two_factor.security_tokens` configuration option.

```yaml
# config/packages/scheb_two_factor.yaml
scheb_two_factor:
    security_tokens:
        - Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken
        - Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken
        - Acme\AuthenticationBundle\Token\CustomAuthenticationToken
```

### Step 6: Enable two-factor authentication methods

If you have installed any of the two-factor authentication methods, you have to enable these separately. Read how to do
this for:

- [`scheb/totp` TOTP authentication](providers/totp.md)
- [`scheb/google-authenticator` Google Authenticator](providers/google.md)
- [`scheb/email` Email authentication](providers/email.md)

### Step 7: Detailed configuration

You probably want to configure some details of the bundle. See the [all configuration options](configuration.md).
