Troubleshooting
===============

How to debug and solve common issue related to the bundle.

TOTP / Google Authenticator code is not accepted
------------------------------------------------

The principle of TOTP/Google Authenticator is that both systems - the server and your device - generate the same
authentication code from a shared secret and the current time. If one of those two components isn't in sync, they'll
generate a different code. Therefore:

#. Most common problem: Make sure the server time and the time on your device are in sync with the actual current time
#. Make sure the secret used in your device matches the secret configured for the account on the server
#. If you're using TOTP, make sure the app is actually supporting the specific TOTP configuration you're using. **The
   Google Authenticator app supports only one specific TOTP configuration (6-digit code, 30sec window, sha1 algorithm)**

The generated authentication code has a time window in which it is valid (30 seconds in Google Authenticator, for TOTP
it depends on your configuration). The bigger the time difference between server and device, the smaller the time
window, the higher the chance that the codes generated on server and from the app don't match up. When the time
difference becomes larger than the time window, it becomes impossible to provide the right code.

To counteract the issue of time differences you could increase the ``window`` setting, then more codes around the
current time window will be accepted:

.. code-block:: yaml

   # config/packages/scheb_2fa.yaml
   scheb_two_factor:

       # For TOTP
       totp:
           window: 1  # How many codes before/after the current one would be accepted as valid

       # For Google Authenticator
       google:
           window: 1  # How many codes before/after the current one would be accepted as valid

You might want to configure a time synchronization service, such as ``ntpdate`` on your server to make sure your server
time is always in sync with UTC.

The Google Authenticator app has an option to sync the time your device. Open the app and select
``Settings > Time correction for codes > Sync now`` from the menu. Other apps might have a similar option.

Logout redirects back to the two-factor authentication form
-----------------------------------------------------------

Problem
~~~~~~~

When the two-factor authentication form is shown, you want to cancel the two-factor authentication process. You click
the "cancel" link, which should execute a logout. It does not execute the logout, but redirects back to the two-factor
authentication form.

Solution
~~~~~~~~

If you see such behavior, the ``access_control`` rules from the security configuration don't allow accessing the logout
path. Your logout path must be accessible to user with any authentication state, which is usually done by allowing it
for ``IS_AUTHENTICATED_ANONYMOUSLY``. You're most likely missing a rule under ``access_control`` in the security
configuration.

The configuration should look similar to this:

.. code-block:: yaml

   # config/packages/security.yaml
   security:
       access_control:
           - { path: ^/logout, role: IS_AUTHENTICATED_ANONYMOUSLY }
           # More rules here ...

Make sure the rule comes first in the list, since access control rules are evaluated in order.

If you have such a rule and it still doesn't work, for some reason the rule is not matching. Make absolutely sure the
``path`` regular expression matches your logout path. If you have additional options, such as ``host`` or ``ip``, check
that they're matching as well.

Not logged in after completing two-factor authentication
--------------------------------------------------------

Problem
~~~~~~~

After you logged in and have successfully passed the two-factor authentication process, you're not logged in. Either you
are redirected back to the login page or the page is shown with the authenticated user missing.

Troubleshooting
~~~~~~~~~~~~~~~

#. Disable two-factor authentication by commenting out all ``two_factor`` settings in the security firewall
   configuration. Try to login. Does it work?

   Yes, it works
       Continue with 2)
   No, it does not work
       Your login process is broken.

       **Solution:** Can't exactly tell what's wrong. Continue debugging the login issue. Solve this issue first,
       before you re-enable two-factor authentication.

#. Revert the changes from 1). Debug the security token on the two-factor authentication form page by ``var_dump``-ing
   it or any other suitable method.

   The token should be of type ``TwoFactorToken`` and the field ``authenticatedToken`` should contain an authenticated
   security token. Does that authenticated token have ``authenticated``=``false`` set?

   Yes
       Your authenticated token was flagged as invalid. Follow solution below.
   No
       Continue with 3)

#. After completing two-factor authentication, when you end up in the unauthenticated state, check the last request few
   requests in the Symfony profiler.

   For each of the requests, go to Logs -> Debug.

   Does it say ``Cannot refresh token because user has changed`` or ``Token was deauthenticated after trying to refresh
   it``?

   Yes
       Your authenticated token was flagged as invalid. Follow solution below.
   No
       Unknown issue. Try to reach out for help by
      :doc:`creating an issue </https://github.com/scheb/2fa/issues/new?labels=Support&template=support-request>` and let us
       know what you've already tested.

**Solution to: Your authenticated token was flagged as invalid**

Most likely your user entity implements the ``\Serializable`` interface and not all of the field relevant to the
authentication process are taken by serialize/unserialize. Check which fields are used in methods ``serialize()`` and
``deserialize()``.

It must be at least the fields that are used in the methods from ``Symfony\Component\Security\Core\User\UserInterface``.

If your user entity implements ``Symfony\Component\Security\Core\User\AdvancedUserInterface``, you also need the fields
that are used in ``isAccountNonExpired()``, ``isAccountNonLocked()``, ``isCredentialsNonExpired()`` and ``isEnabled()``.

Two-factor authentication form is not shown after login
-------------------------------------------------------

Problem
~~~~~~~

After successful login, the two-factor authentication form is not shown. Instead, you're either logged in or you see
a different page from your application.

Basic checks
~~~~~~~~~~~~

* Your login page belongs to the firewall, which has two-factor authentication configured.
* The paths of login page, login check, 2fa and 2fa check are all located with the firewall's path ``pattern``.
* Your user entity has the interfaces implemented, which are necessary for the two-factor authentication method.
* Your user entity fulfills the requirements of at least one two-factor authentication method:

  * The ``is*Enabled()`` method returns ``true``
  * Additional data for the authentication method is returned, e.g. for Google Authenticator to work the
    ``getGoogleAuthenticatorSecret()`` method must return a secret code.

Is ``access_control`` configured properly?
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

To make the two-factor authentication form accessible during the two-factor authentication process, you have to
configure a ``access_control`` rule for the 2fa routes:

The configuration should look similar to this:

.. code-block:: yaml

   # config/packages/security.yaml
   security:
       # IMPORTANT: THE ACCESS CONTROL RULE NEEDS TO BE AT THE VERY TOP OF THE LIST!
       access_control:
           # This ensures that the form can only be accessed when two-factor authentication is in progress.
           # The path may be different, depending on how you've configured the route.
           - { path: ^/2fa, role: IS_AUTHENTICATED_2FA_IN_PROGRESS }
           # Other rules may follow here...

**Make sure the rule comes first in the list**, since access control rules are evaluated in order.

If you already have such a rule at the top of the list, make sure the ``path`` regular expression matches your
two-factor authentication form path. If you have additional options, such as ``host`` or ``ip``, check that they're
matching as well.

Is there something special about your security setup?
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Often issues originate from a customization in the application's security setup, which is usually related to how roles
are granted. Examples of such issue are:

* `Roles are dynamically granted by a voter, which isn't aware of the intermediate 2fa state <https://github.com/scheb/2fa/issues/23>`_
* `Roles are loaded by replacing the security token after login, effectively skipping 2fa <https://github.com/scheb/two-factor-bundle/issues/289>`_
* `An exception thrown in a voter <https://github.com/scheb/two-factor-bundle/issues/291>`_

For 2fa to work properly, there must be two things fulfilled: A ``TwoFactorToken`` must be present after login and
within that intermediate "2fa incomplete" state no roles must be granted. That later one is achieved by
``TwoFactorToken`` not returning any roles on the ``getRoleNames()`` call. But if you grant roles differently other than
through the token, things will break.

The solution to this problem is usually to skip any customization for a security token of type
``TwoFactorTokenInterface``.

.. code-block:: php

   <?php
   use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;

   if (!($token instanceof TwoFactorTokenInterface)) {
       // Your customization here
   }

Troubleshooting
~~~~~~~~~~~~~~~

#. Is a ``TwoFactorToken`` present after the login?

   Yes
       Continue with 2)
   No
       Continue with 3)

#. Try accessing a page that requires the user to be authenticated. Does it redirect to the two-factor authentication
   form?

   Yes
       **Solution:** The page you've seen after login doesn't require a fully authenticated user. Most likely that
       path is accessible to ``IS_AUTHENTICATED_ANONYMOUSLY`` via your security ``access_control`` configuration. Either
       change your ``access_control`` configuration or after login force-redirect to user to a page that requires full
       authentication.
   No
       Unknown issue. Try to reach out for help by
       :doc:`creating an issue </https://github.com/scheb/2fa/issues/new?labels=Support&template=support-request>` and let us
       know what you've already tested.

#. On login, do you reach the end (return statement) of method
   ``Scheb\TwoFactorBundle\Security\Authentication\Provider\AuthenticationProviderDecorator::authenticate()``?

   Yes
       Continue with 4)
   No
       Something is wrong with the integration of the bundle. Try to reach out for help by
      :doc:`creating an issue </https://github.com/scheb/2fa/issues/new?labels=Support&template=support-request>` and let us
      know what you've already tested.

#. On login, is method
   ``Scheb\TwoFactorBundle\Security\TwoFactor\Handler\TwoFactorProviderHandler::getActiveTwoFactorProviders()`` called?

   Yes, it's called
       Continue with 5)
   No it's not called
       **Solution:** Two-factor authentication is skipped, either because of the IP whitelist or because of a trusted
       device token. IP whitelist is part of the bundle's configuration. Maybe you have whitelisted "localhost" or
       "127.0.0.1"? The trusted device cookie can be removed with your browser's developer tools.

5) Does ``Scheb\TwoFactorBundle\Security\TwoFactor\Handler\TwoFactorProviderHandler::getActiveTwoFactorProviders()``
   return any values?

   Yes, it returns an array of strings
       Unknown issue. Try to reach out for help by
       :doc:`creating an issue </https://github.com/scheb/2fa/issues/new?labels=Support&template=support-request>` and let us
       know what you've already tested.
   No, it returns an empty array
       **Solution:** our user doesn't have an active two-factor authentication method. Either the ``is*Enabled`` method
       returns ``false`` or an essential piece of data (e.g. Google Authenticator secret) is missing.

Trusted device cookie is not set
--------------------------------

Problem
^^^^^^^

After you have completed 2fa, you expect that device to be flagged as a "trusted device", but the
trusted device cookie is not set.

Basic checks
^^^^^^^^^^^^

* 2fa was completed with that call and you've been fully authenticated afterwards.
* Together with the 2fa code, you have sent the trusted parameter (default ``_trusted``) with a
  ``true``-like value. (Background information: Devices are not automatically flagged as trusted. The user has to choose
  if they can trust that device. That's why this extra parameter has to be sent over.)

Troubleshooting
^^^^^^^^^^^^^^^

Have a look at the response of the HTTP call when you sent over the 2fa and the trusted parameter. Do you see a cookie
being set (``Set-Cookie`` header)?

Yes
    Please validate the cookie's parameters. Make sure everything is fine for that cookie: the path, domain, and
    other cookie options. Did you maybe try to `set it for a top level domain <https://github.com/scheb/two-factor-bundle/issues/242#issuecomment-538735430>`_\ ?
No, there's no cookie set
    Unknown issue. Try to reach out for help by
    :doc:`creating an issue </https://github.com/scheb/2fa/issues/new?labels=Support&template=support-request>` and let us
    know what you've already tested.
