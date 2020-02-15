scheb/2fa
=========

[![Build Status](https://travis-ci.org/scheb/2fa.svg?branch=master)](https://travis-ci.org/scheb/2fa)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/scheb/2fa/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/scheb/2fa/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/scheb/2fa/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/scheb/2fa/?branch=master)
[![Latest Stable Version](https://poser.pugx.org/scheb/2fa/v/stable.svg)](https://packagist.org/packages/scheb/2fa)
[![Total Downloads](https://poser.pugx.org/scheb/2fa/downloads)](https://packagist.org/packages/scheb/2fa)
[![License](https://poser.pugx.org/scheb/2fa/license.svg)](https://packagist.org/packages/scheb/2fa)

This bundle provides **[two-factor authentication](https://en.wikipedia.org/wiki/Multi-factor_authentication) for your
[Symfony](https://symfony.com/) application**.

---

The bundle is organized into sub-repositories, so you can choose the exact feature set you need and keep installed
dependencies to a minimum.

Core features provided by `scheb/2fa-bundle`:

- Interface for custom two-factor authentication methods
- Trusted IPs
- Multi-factor authentication (more than 2 steps)
- CSRF protection
- Whitelisted routes (accessible during two-factor authentication)

Additional features:

- Trusted devices (once passed, no more two-factor authentication on that device) (`scheb/2fa-trusted-devices`)
- Single-use backup codes for when you don't have access to the second factor device (`scheb/2fa-backup-code`)

Two-factor authentication methods:

- [TOTP authentication](https://en.wikipedia.org/wiki/Time-based_One-time_Password_algorithm) (`scheb/2fa-totp`)
- [Google Authenticator](https://en.wikipedia.org/wiki/Google_Authenticator)  (`scheb/2fa-google-authenticator`)
- Authentication code via email (`scheb/2fa-email`)

Installation
-------------
Follow the [installation instructions](doc/installation.md).

Documentation
-------------
Detailed documentation of all features can be found in the [doc](doc/index.md) directory.

Compatibility
-------------
- **Recommended version:** Bundle version 5.x is compatible with Symfony 4.4, and 5.x.
- [Bundle version 4.x](https://github.com/scheb/two-factor-bundle) (managed in a different repository) is compatible
  with Symfony 3.4, 4.x and 5.x.

Previous versions are no longer maintained, please consider upgrading.

Security Issues
---------------
If you think that you have found a security issue in the bundle, don't use the bug tracker and don't publish it
publicly. Instead, please report via email to me@christianscheb.de.

Contributing
------------
See [CONTRIBUTING.md](CONTRIBUTING.md).

License
-------
This software is available under the [MIT license](LICENSE).
