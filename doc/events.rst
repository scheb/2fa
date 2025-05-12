Events
======

Authentication Events
---------------------

The bundle dispatches the following events during the authentication process:

``scheb_two_factor.authentication.require``
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Constant: ``Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvents::REQUIRE``

Event class: ``Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvent``

Is dispatched when two-factor authentication is required for the user. This happens when you try to access a path that
requires you to be fully authenticated. It also happens when you successfully complete a two-factor authentication step,
but there's another two-factor step required (multi-factor authentication).

Usually, when this event is dispatched, the request is redirected to the two-factor authentication form.

``scheb_two_factor.authentication.form``
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Constant: ``Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvents::FORM``

Event class: ``Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvent``

Is dispatched when the two-factor authentication form is shown.

``scheb_two_factor.authentication.attempt``
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Constant: ``Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvents::ATTEMPT``

Event class: ``Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvent``

Is dispatched when two-factor authentication is attempted, right before checking the code.

``scheb_two_factor.authentication.success``
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Constant: ``Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvents::SUCCESS``

Event class: ``Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvent``

Is dispatched when two-factor authentication was successful for a single provider. That doesn't mean the entire
two-factor process is completed.

``scheb_two_factor.authentication.failure``
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Constant: ``Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvents::FAILURE``

Event class: ``Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvent``

Is dispatched when the given two-factor authentication code was incorrect.

``scheb_two_factor.authentication.complete``
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Constant: ``Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvents::COMPLETE``

Event class: ``Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvent``

Is dispatched when the entire two-factor authentication process was completed successfully, that means two-factor
authentication code was correct for all providers required and the user is now fully authenticated.

Code Check Events
-----------------

The following events are dispatched before the actual authentication provider is used

``scheb_two_factor.authentication.check``
~~~~~~~~~~~~~~~~~~~~~~~~~

Constant: ``Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvents::CHECK``

Event class: ``\Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorCodeCheckEvent``

Is dispatched before the TOTP authentication provider is used to check the code for plausibility

``scheb_two_factor.authentication.code_reused``
~~~~~~~~~~~~~~~~~~~~~~~~~~

Constant: ``Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvents::CODE_REUSED``

Event class: ``\Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorCodeReusedEvent``

Is dispatched when the code has been used already within the last 30 seconds.
This requires a caching backend to be available


Backup Code Events
------------------

If you have the backup codes extension installed, the following events are dispatched:

``scheb_two_factor.backup_code.check``
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Constant: ``Scheb\TwoFactorBundle\Security\TwoFactor\Event\BackupCodeEvents::CHECK``

Event class: ``Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorCodeEvent``

Is dispatched whenever a code is checked if it is a valid backup code.

``scheb_two_factor.backup_code.valid``
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Constant: ``Scheb\TwoFactorBundle\Security\TwoFactor\Event\BackupCodeEvents::VALID``

Event class: ``Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorCodeEvent``

Is dispatched when the code was deemed to be a valid backup code.

``scheb_two_factor.backup_code.invalid``
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Constant: ``Scheb\TwoFactorBundle\Security\TwoFactor\Event\BackupCodeEvents::INVALID``

Event class: ``Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorCodeEvent``

Is dispatched when the code was deemed to be a invalid backup code.


Email Authentication Events
---------------------------

The following events are dispatched when the email code authentication provider is used:

``scheb_two_factor.provider.email.sent``
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Constant: ``Scheb\TwoFactorBundle\Security\TwoFactor\Event\EmailCodeEvents::SENT``

Event class: ``Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorCodeEvent``

Is dispatched whenever a code was sent via email to the user.

``scheb_two_factor.provider.email.check``
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Constant: ``Scheb\TwoFactorBundle\Security\TwoFactor\Event\EmailCodeEvents::CHECK``

Event class: ``Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorCodeEvent``

Is dispatched whenever a code is checked if it is a valid email code.

``scheb_two_factor.provider.email.valid``
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Constant: ``Scheb\TwoFactorBundle\Security\TwoFactor\Event\EmailCodeEvents::VALID``

Event class: ``Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorCodeEvent``

Is dispatched when the code was deemed to be a valid email code.

``scheb_two_factor.provider.email.invalid``
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Constant: ``Scheb\TwoFactorBundle\Security\TwoFactor\Event\EmailCodeEvents::INVALID``

Event class: ``Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorCodeEvent``

Is dispatched when the code was deemed to be a invalid email code.


Google Authenticator Events
---------------------------

The following events are dispatched when the Google Authenticator authentication provider is used:

``scheb_two_factor.provider.google.check``
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Constant: ``Scheb\TwoFactorBundle\Security\TwoFactor\Event\GoogleAuthenticatorCodeEvents::CHECK``

Event class: ``Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorCodeEvent``

Is dispatched whenever a code is checked if it is a valid Google Authenticator code.

``scheb_two_factor.provider.google.valid``
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Constant: ``Scheb\TwoFactorBundle\Security\TwoFactor\Event\GoogleAuthenticatorCodeEvents::VALID``

Event class: ``Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorCodeEvent``

Is dispatched when the code was deemed to be a valid Google Authenticator code.

``scheb_two_factor.provider.google.invalid``
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Constant: ``Scheb\TwoFactorBundle\Security\TwoFactor\Event\GoogleAuthenticatorCodeEvents::INVALID``

Event class: ``Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorCodeEvent``

Is dispatched when the code was deemed to be a invalid Google Authenticator code.


TOTP Authentication Events
--------------------------

The following events are dispatched when the TOTP authentication provider is used:

``scheb_two_factor.provider.totp.check``
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Constant: ``Scheb\TwoFactorBundle\Security\TwoFactor\Event\TotpCodeEvents::CHECK``

Event class: ``Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorCodeEvent``

Is dispatched whenever a code is checked if it is a valid TOTP code.

``scheb_two_factor.provider.totp.valid``
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Constant: ``Scheb\TwoFactorBundle\Security\TwoFactor\Event\TotpCodeEvents::VALID``

Event class: ``Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorCodeEvent``

Is dispatched when the code was deemed to be a valid TOTP code.

``scheb_two_factor.provider.totp.invalid``
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Constant: ``Scheb\TwoFactorBundle\Security\TwoFactor\Event\TotpCodeEvents::INVALID``

Event class: ``Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorCodeEvent``

Is dispatched when the code was deemed to be a invalid TOTP code.
