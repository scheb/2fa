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
