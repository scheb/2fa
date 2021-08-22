SchebTwoFactorBundle
====================

This bundle provides **two-factor authentication for your Symfony application**.

Index
-----

* :doc:`Installation </installation>`
* :doc:`Configuration Reference </configuration>`
* :doc:`Trusted Devices </trusted_device>`
* :doc:`Backup Codes </backup_codes>`
* :doc:`Brute Force Protection </brute_force_protection>`
* :doc:`CSRF Protection </csrf_protection>`
* :doc:`Events </events>`
* :doc:`Troubleshooting (common issues) </troubleshooting>`

**How-to's:**

* :doc:`How to create a custom two-factor authenticator </providers/custom>`
* :doc:`How to handle multiple activated authentication methods </multi_authentication>`
* :doc:`How to customize conditions when to require two-factor authentication </custom_conditions>`
* :doc:`How to configure two-factor authentication for an API </api>`
* :doc:`How to create a custom persister </persister>`
* :doc:`How to use a different template per firewall </firewall_template>`

Two-Factor Authentication Methods
---------------------------------

The bundle supports the following authentication methods out of the box:

* :doc:`Google Authenticator </providers/google>`
* :doc:`TOTP Authenticator </providers/totp>`
* :doc:`Code-via-Email authentication </providers/email>`

See :doc:`Providers </providers/index>` for more information about custom or third-party provider.

The Authentication Process
--------------------------

The bundle hocks into security layer and listens for authentication events. When a login happens and the user has
two-factor authentication enabled, access and privileges are temporary withhold from the user. Instead, the user is
challenged to enter a valid two-factor authentication code. Only when that code is entered correctly, the roles are
granted.

.. image:: authentication-process.png
   :alt: Authentication process

To represent the state between login and a valid two-factor code being entered, the bundle introduces the role-like
attribute ``IS_AUTHENTICATED_2FA_IN_PROGRESS``, which can be used in ``is_granted()`` calls. ``IS_AUTHENTICATED_FULLY``
is, just like roles, withhold until the two-factor authentication step has been completed successfully.
