scheb/two-factor-bundle
=======================

[![Build Status](https://travis-ci.org/scheb/two-factor-bundle.svg?branch=master)](https://travis-ci.org/scheb/two-factor-bundle)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/scheb/two-factor-bundle/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/scheb/two-factor-bundle/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/scheb/two-factor-bundle/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/scheb/two-factor-bundle/?branch=master)
[![Latest Stable Version](https://poser.pugx.org/scheb/two-factor-bundle/v/stable.svg)](https://packagist.org/packages/scheb/two-factor-bundle)
[![Total Downloads](https://poser.pugx.org/scheb/two-factor-bundle/downloads)](https://packagist.org/packages/scheb/two-factor-bundle)
[![License](https://poser.pugx.org/scheb/two-factor-bundle/license.svg)](https://packagist.org/packages/scheb/two-factor-bundle)

This bundle provides **[two-factor authentication](https://en.wikipedia.org/wiki/Multi-factor_authentication) for your
[Symfony](https://symfony.com/) application**.

It comes with the following two-factor authentication methods:

- [TOTP authentication](https://en.wikipedia.org/wiki/Time-based_One-time_Password_algorithm)
- [Google Authenticator](https://en.wikipedia.org/wiki/Google_Authenticator)
- Authentication code via email

Additional features you will like:

- Interface for custom two-factor authentication methods
- Trusted IPs
- Trusted devices (once passed, no more two-factor authentication on that device)
- Single-use backup codes for when you don't have access to the second factor device
- Multi-factor authentication (more than 2 steps)
- CSRF protection
- Whitelisted routes (accessible during two-factor authentication)

Installation
-------------

```bash
composer require scheb/two-factor-bundle
```

... and follow the [installation instructions](Resources/doc/installation.md).

Documentation
-------------
Detailed documentation of all features can be found in the [Resources/doc](Resources/doc/index.md) directory.

Compatibility
-------------
- **Recommended version:** Bundle version 4.x is compatible with Symfony 3.4, 4.x and 5.x.
- Bundle version 3.x is compatible with Symfony 3.4, 4.x and 5.x.

Previous versions are no longer maintained, please consider upgrading.

Security Issues
---------------
If you think that you have found a security issue in the bundle, don't use the bug tracker and don't publish it
publicly. Instead, please report via email to me@christianscheb.de.

Known security issues:

- Before version 3.7 the bundle is vulnerable to a
[security issue in JWT](https://auth0.com/blog/critical-vulnerabilities-in-json-web-token-libraries/), which can be
exploited by an attacker to generate trusted device cookies on their own, effectively by-passing two-factor
authentication. ([#143](https://github.com/scheb/two-factor-bundle/issues/143))

- Before versions 3.26.0 / 4.11.0 it was possible to bypass two-factor authentication when the remember-me option is
available on the login form. ([#253](https://github.com/scheb/two-factor-bundle/issues/253))

Contribute
----------
You're welcome to [contribute](https://github.com/scheb/two-factor-bundle/graphs/contributors) to this bundle by
creating a pull requests or feature request in the issues section. For pull requests, please follow these guidelines:

- Symfony code style (use `php_cs.xml` to configure the code style in your IDE)
- PHP7.1 type hints for everything (including: return types, `void`, nullable types)
- `declare(strict_types=1)` must be used
- Please add/update test cases
- Test methods should be named `[method]_[scenario]_[expected result]`

Besides new features, [translations](Resources/translations) are highly welcome.

To run the test suite install the dependencies with `composer install` and then execute `bin/phpunit`.

License
-------
This bundle is available under the [MIT license](LICENSE).
