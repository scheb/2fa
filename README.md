scheb/2fa
=========

### ⚠ Unmaintained version

Please upgrade your project to a recent version. See [version guidance](https://github.com/scheb/2fa#version-guidance)
on the default branch for maintained versions.

---

This bundle provides **[two-factor authentication](https://en.wikipedia.org/wiki/Multi-factor_authentication) for your
[Symfony](https://symfony.com/) application**.

[![Build Status](https://github.com/scheb/2fa/workflows/CI/badge.svg?branch=5.x)](https://github.com/scheb/2fa/actions?query=workflow%3ACI+branch%3A5.x)
[![Code Coverage](https://codecov.io/gh/scheb/2fa/branch/5.x/graph/badge.svg)](https://app.codecov.io/gh/scheb/2fa/branch/5.x)
[![Latest Stable Version](https://img.shields.io/packagist/v/scheb/2fa-bundle)](https://packagist.org/packages/scheb/2fa-bundle)
[![Monthly Downloads](https://img.shields.io/packagist/dm/scheb/2fa-bundle)](https://packagist.org/packages/scheb/2fa-bundle/stats)
[![Total Downloads](https://img.shields.io/packagist/dt/scheb/2fa-bundle)](https://packagist.org/packages/scheb/2fa-bundle/stats)
[![License](https://poser.pugx.org/scheb/2fa/license.svg)](https://packagist.org/packages/scheb/2fa)

<p align="center"><img alt="SchebTwoFactorBundle Logo" src="doc/2fa-logo.svg" /></p>

ℹ️ The repository contains bundle versions ≥ 5, which are compatible with Symfony 4.4 or later. The older (unsupported)
   versions are located in the [scheb/two-factor-bundle](https://github.com/scheb/two-factor-bundle) repository.

---

The bundle is split into sub-packages, so you can choose the exact feature set you need and keep installed dependencies
to a minimum.

Core features are provided by `scheb/2fa-bundle`:

- Interface for custom two-factor authentication methods
- Trusted IPs
- Multi-factor authentication (more than 2 steps)
- CSRF protection
- Whitelisted routes (accessible during two-factor authentication)
- Fully customizable conditions when to perform two-factor authentication
- Future proof: Supports the [authenticator-based security system](https://symfony.com/doc/current/security/experimental_authenticators.html),
  which will replace the current system in Symfony 6

Additional features:

- Trusted devices (once passed, no more two-factor authentication on that device) (`scheb/2fa-trusted-device`)
- Single-use backup codes for when you don't have access to the second factor device (`scheb/2fa-backup-code`)
- QR codes to scan with your mobile device (`scheb/2fa-qr-code`)

Two-factor authentication methods:

- [TOTP authentication](https://en.wikipedia.org/wiki/Time-based_One-time_Password_algorithm) (`scheb/2fa-totp`)
- [Google Authenticator](https://en.wikipedia.org/wiki/Google_Authenticator) (`scheb/2fa-google-authenticator`)
- Authentication code via email (`scheb/2fa-email`)

Installation
-------------
Follow the [installation instructions](https://symfony.com/bundles/SchebTwoFactorBundle/5.x/installation.html).

Documentation
-------------
Detailed documentation of all features can be found on the
[Symfony Bundles Documentation](https://symfony.com/bundles/SchebTwoFactorBundle/5.x/index.html) website.

Demo
----
This repository contains a small test application that can be quickly set-up locally to test two-factor authentication
in a real Symfony environment. Check out the readme file in the [`app` folder](app/README.md) for more details.

Version Guidance
----------------

**⚠ Version 5.x is no longer maintained.**

Please upgrade your project to a recent version. See [version guidance](https://github.com/scheb/2fa#version-guidance)
on the default branch for maintained versions.

License
-------
This software is available under the [MIT license](LICENSE).

Security
--------
For information about the security policy and know security issues, see [SECURITY.md](SECURITY.md).

Contributing
------------
Want to contribute to this project? See [CONTRIBUTING.md](CONTRIBUTING.md).

Support Me
----------
I'm developing this library since 2014. I love to hear from people using it, giving me the motivation to keep working
on my open source projects.

If you want to let me know you're finding it useful, please consider giving it a star ⭐ on GitHub.

If you love my work and want to say thank you, you can help me out for a beer 🍻️
[via PayPal](https://paypal.me/ChristianScheb).
