Implementing a custom two-factor provider
=========================================

Getting started
---------------

A good starting point are the Google Authenticator, TOTP and email authentication implementations, which are available
in the codebase. Have a look at the follow files:

* `src/google-authenticator/Security/TwoFactor/Provider/Google/GoogleAuthenticatorTwoFactorProvider.php <https://github.com/scheb/2fa/tree/6.x/src/google-authenticator/Security/TwoFactor/Provider/Google/GoogleAuthenticatorTwoFactorProvider.php>`_
* `src/totp/Security/TwoFactor/Provider/Totp/TotpAuthenticatorTwoFactorProvider.php <https://github.com/scheb/2fa/tree/6.x/src/totp/Security/TwoFactor/Provider/Totp/TotpAuthenticatorTwoFactorProvider.php>`_
* `src/email/Security/TwoFactor/Provider/Email/EmailTwoFactorProvider.php <https://github.com/scheb/2fa/tree/6.x/src/email/Security/TwoFactor/Provider/Email/EmailTwoFactorProvider.php>`_

You will get the basic idea how to implement a custom two-factor method.

The TwoFactorProviderInterface
------------------------------

You have to create a service, which implements the
``Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderInterface`` interface. It requires these methods:

beginAuthentication
~~~~~~~~~~~~~~~~~~~

.. code-block:: php

   public function beginAuthentication(AuthenticationContextInterface $context): bool;

The method is called after successful login. It receives an ``AuthenticationContextInterface`` object as the argument
(see class ``Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContext``) which contains the request object the
authentication token, the user entity and other information.

The method has to decide if the user should be asked for two-factor authentication from that provider. In that case
return ``true``, otherwise ``false``.

.. code-block:: php

   public function prepareAuthentication(object $user): void;

This method is where you should do the preparation work for your two-factor provider. E.g. the *email* provider is
generating a code and sending it to the user.

validateAuthenticationCode
~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: php

   public function validateAuthenticationCode(object $user, string $authenticationCode): bool;

This method is responsible for validating the authentication code entered by the user. Return ``true`` if the code was
correct or ``false`` when it was wrong.

getFormRenderer
~~~~~~~~~~~~~~~

.. code-block:: php

   public function getFormRenderer(): TwoFactorFormRendererInterface;

This method has to provide a service for rendering the authentication form. Such a service has to implement the
``Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorFormRendererInterface`` interface:

.. code-block:: php

   public function renderForm(Request $request, array $templateVars): Response;

How you render the form is totally up to you. The only important thing is to return a ``Response``, which could also be
a ``RedirectResponse`` redirect to an external service. A default implementation for rendering forms with Twig is
available as ``Scheb\TwoFactorBundle\Security\TwoFactor\Provider\DefaultTwoFactorFormRenderer``.

Register the provider
---------------------

Now you have to register your two-factor provider class as a service.

A tag named ``scheb_two_factor.provider`` will make your provider available to the bundle. The tag attribute ``alias``
has to be set and must be an application-wide unique identifier for the authentication provider.

.. note::

    The aliases ``google``, ``totp`` and ``email`` are reserved by the authentication methods that are
    included in the bundle.

.. configuration-block::

    .. code-block:: yaml

       # config/services.yaml
       services:
           # ...
           acme.custom_two_factor_provider:
               class: Acme\Demo\MyTwoFactorProvider
               tags:
                   - { name: scheb_two_factor.provider, alias: acme_two_factor_provider }

    .. code-block:: xml

       <service id="acme.custom_two_factor_provider" class="Acme\Demo\MyTwoFactorProvider">
           <tag name="scheb_two_factor.provider" alias="acme_two_factor_provider" />
       </service>
