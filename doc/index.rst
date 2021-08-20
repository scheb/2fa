scheb/2fa
=========

This bundle provides **two-factor authentication for your Symfony application**.

Index
-----


* `Installation <installation.rst>`_
* `Configuration Reference <configuration.rst>`_
* `Trusted Devices <trusted_device.rst>`_
* `Backup Codes <backup_codes.rst>`_
* `Brute Force Protection <brute_force_protection.rst>`_
* `CSRF Protection <csrf_protection.rst>`_
* `Events <events.rst>`_
* `Troubleshooting (common issues) <troubleshooting.rst>`_

**How-to's:**


* `How to create a custom two-factor authenticator <providers/custom.rst>`_
* `How to handle multiple activated authentication methods <multi_authentication.rst>`_
* `How to customize conditions when to require two-factor authentication <custom_conditions.rst>`_
* `How to configure two-factor authentication for an API <api.rst>`_
* `How to create a custom persister <persister.rst>`_
* `How to use a different template per firewall <firewall_template.rst>`_

Two-Factor Authentication Methods
---------------------------------

The bundle supports the following authentication methods out of the box:


* `Google Authenticator <providers/google.rst>`_
* `TOTP Authenticator <providers/totp.rst>`_
* `Email authentication code <providers/email.rst>`_

See `Providers <providers/index.rst>`_ for more information about custom or third-party provider.

The Authentication Process
--------------------------

The bundle hocks into security layer and listens for authentication events. When a login happens and the user has
two-factor authentication enabled, access and privileges are temporary withhold from the user. Instead, the user is
challenged to enter a valid two-factor authentication code. Only when that code is entered correctly, the roles are
granted.


.. image:: authentication-process.png
   :target: authentication-process.png
   :alt: Authentication process


To represent the state between login and a valid two-factor code being entered, the bundle introduces the role-like
attribute ``IS_AUTHENTICATED_2FA_IN_PROGRESS``\ , which can be used in ``is_granted()`` calls. ``IS_AUTHENTICATED_FULLY`` is,
just like roles, withhold until the two-factor authentication step has been completed successfully.
