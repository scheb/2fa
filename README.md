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

Version Guidance
----------------

| Version        | Status         | Symfony Version  |
|----------------|----------------|------------------|
| [1.x][v1-repo] | EOL            | >= 2.1, < 2.7    |
| [2.x][v2-repo] | EOL            | ^2.6, ^3.0, ^4.0 |
| [3.x][v3-repo] | Maintained     | 3.4, ^4.0, ^5.0  |
| [4.x][v4-repo] | Maintained     | 3.4, ^4.0, ^5.0  |
| [5.x][v5-repo] | In Development | 4.4, ^5.0  |

[v1-repo]: https://github.com/scheb/two-factor-bundle/tree/1.x
[v2-repo]: https://github.com/scheb/two-factor-bundle/tree/2.x
[v3-repo]: https://github.com/scheb/two-factor-bundle/tree/3.x
[v4-repo]: https://github.com/scheb/two-factor-bundle/tree/master
[v5-repo]: https://github.com/scheb/2fa/tree/master

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
