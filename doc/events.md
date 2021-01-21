Events
======

The bundle dispatches the following events during the authentication process:

## `scheb_two_factor.authentication.require`

Constant: `Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvents::REQUIRE`

Is dispatched when two-factor authentication is required for the user. This happens when you try to access a path that
requires you to be fully authenticated. It also happens when you successfully complete a two-factor authentication step,
but there's another two-factor step required (multi-factor authentication).

Usually, when this event is dispatched, the request is redirected to the two-factor authentication form.

## `scheb_two_factor.authentication.form`

Constant: `Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvents::FORM`

Is dispatched when the two-factor authentication form is shown.

## `scheb_two_factor.authentication.attempt`

Constant: `Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvents::ATTEMPT`

Is dispatched when two-factor authentication is attempted, right before checking the code.

## `scheb_two_factor.authentication.success`

Constant: `Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvents::SUCCESS`

Is dispatched when two-factor authentication was successful for a single provider. That doesn't mean the entire
two-factor process is completed.

## `scheb_two_factor.authentication.failure`

Constant: `Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvents::FAILURE`

Is dispatched when the given two-factor authentication code was incorrect.

## `scheb_two_factor.authentication.complete`

Constant: `Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvents::COMPLETE`

Is dispatched when the entire two-factor authentication process was completed successfully, that means two-factor
authentication code was correct for all providers required and the user is now fully authenticated.
