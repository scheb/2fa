scheb/2fa
=========

This bundle provides **two-factor authentication for your Symfony application**.

## Index

- [Installation](installation.md)
- [Configuration Reference](configuration.md)
- [Trusted Devices](trusted_device.md)
- [Backup Codes](backup_codes.md)
- [Brute Force Protection](brute_force_protection.md)
- [CSRF Protection](csrf_protection.md)
- [Events](events.md)
- [Troubleshooting (common issues)](troubleshooting.md)

**How-to's:**

- [How to create a custom two-factor authenticator](providers/custom.md)
- [How to handle multiple activated authentication methods](multi_authentication.md)
- [How to customize conditions when to require two-factor authentication](custom_conditions.md)
- [How to configure two-factor authentication for an API](api.md)
- [How to create a custom persister](persister.md)
- [How to use a different template per firewall](firewall_template.md)


## Two-Factor Authentication Methods

The bundle supports the following authentication methods out of the box:

  - [Webauthn](providers/webauthn.md)
  - [Google Authenticator](providers/google.md)
  - [TOTP Authenticator](providers/totp.md)
  - [Email authentication code](providers/email.md)

See [Providers](providers/index.md) for more information about custom or third-party provider.


## The Authentication Process

The bundle hocks into security layer and listens for authentication events. When a login happens and the user has
two-factor authentication enabled, access and privileges are temporary withhold from the user. Instead, the user is
challenged to enter a valid two-factor authentication code. Only when that code is entered correctly, the roles are
granted.

![Authentication process](authentication-process.png)

To represent the state between login and a valid two-factor code being entered, the bundle introduces the role-like
attribute `IS_AUTHENTICATED_2FA_IN_PROGRESS`, which can be used in `is_granted()` calls. `IS_AUTHENTICATED_FULLY` is,
just like roles, withhold until the two-factor authentication step has been completed successfully.
