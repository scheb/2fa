Installation
============

Prerequisites
-------------

You're currently looking at the documentation of **SchebTwoFactorBundle version 6**. This bundle version is
**compatible with Symfony 5.4 or Symfony 6.x**.

If you're using anything other than Doctrine ORM to manage the user entity you will have to implement a
:doc:`persister service </persister>`.

Installation
------------

Step 1: Install with Composer
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

The bundle is organized into sub-repositories, so you can choose the exact feature set you need and keep installed
dependencies to a minimum.

If you're using `Symfony Flex <https://flex.symfony.com/>`_, use the following command to install the bundle via
`Composer <https://getcomposer.org>`_:

.. code-block:: terminal

   composer require 2fa

Alternatively, use the following Composer command:

.. code-block:: terminal

   composer require scheb/2fa-bundle

Optionally, install any additional packages to extend the bundle's feature according to your needs:

.. code-block:: terminal

   composer require scheb/2fa-backup-code            # Add backup code feature
   composer require scheb/2fa-trusted-device         # Add trusted devices feature
   composer require scheb/2fa-totp                   # Add two-factor authentication using TOTP
   composer require scheb/2fa-google-authenticator   # Add two-factor authentication with Google Authenticator
   composer require scheb/2fa-email                  # Add two-factor authentication using email

Step 2: Enable the bundle
~~~~~~~~~~~~~~~~~~~~~~~~~

.. note::

    If you're using Symfony Flex, this step happens automatically.

Enable this bundle in your ``config/bundles.php``:

.. code-block:: php

   return [
       // ...
       Scheb\TwoFactorBundle\SchebTwoFactorBundle::class => ['all' => true],
   ];

Step 3: Define routes
^^^^^^^^^^^^^^^^^^^^^

.. note::

    If you're using Symfony Flex, a default config file is created automatically. Though make sure the
    preconfigured paths are located within your firewall's ``pattern``.

In ``config/routes/scheb_2fa.yaml`` (create the file if it doesn't exist) you need to add two routes:

* a route for the two-factor authentication form
* another route for checking the two-factor authentication code

The routes must be **located within the path** ``pattern`` **of the firewall**, the one which uses two-factor
authentication.

.. code-block:: yaml

   # config/routes/scheb_2fa.yaml
   2fa_login:
       path: /2fa
       defaults:
           # "scheb_two_factor.form_controller" references the controller service provided by the bundle.
           # You don't HAVE to use it, but - except you have very special requirements - it is recommended.
           _controller: "scheb_two_factor.form_controller:form"

   2fa_login_check:
       path: /2fa_check

If you have multiple firewalls with two-factor authentication, each one needs its own set of login and
check routes that must be located within the associated firewall's path ``pattern``.

Step 4: Configure the firewall
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Enable two-factor authentication **per firewall** and configure ``access_control`` for the 2fa routes:

.. code-block:: yaml

   # config/packages/security.yaml
   security:
       firewalls:
           your_firewall_name:
               two_factor:
                   auth_form_path: 2fa_login    # The route name you have used in the routes.yaml
                   check_path: 2fa_login_check  # The route name you have used in the routes.yaml

       # The path patterns shown here have to be updated according to your routes.
       # IMPORTANT: ADD THESE ACCESS CONTROL RULES AT THE VERY TOP OF THE LIST!
       access_control:
           # This makes the logout route accessible during two-factor authentication. Allows the user to
           # cancel two-factor authentication, if they need to.
           - { path: ^/logout, role: PUBLIC_ACCESS }
           # This ensures that the form can only be accessed when two-factor authentication is in progress.
           - { path: ^/2fa, role: IS_AUTHENTICATED_2FA_IN_PROGRESS }
           # Other rules may follow here...

More per-firewall configuration options can be found in the :doc:`configuration reference </configuration>`.

Step 5: Configure the security tokens
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Your firewall may offer different ways to login. By default (without any configuration), the bundle is listening
only to these tokens:

* ``Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken`` (username+password authentication)
* ``Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken`` (default token used by authenticators)

If you want to support two-factor authentication with another login method, you have to register its token class in the
``scheb_two_factor.security_tokens`` configuration option.

.. code-block:: yaml

   # config/packages/scheb_2fa.yaml
   scheb_two_factor:
       security_tokens:
           - Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken
           - Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken
           - Acme\AuthenticationBundle\Token\CustomAuthenticationToken

Step 6: Enable two-factor authentication methods
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

If you have installed any of the two-factor authentication methods provided as sub-packages, you have to enable these
separately. Read how to do this for:

* ``scheb/2fa-totp`` :doc:`TOTP authentication </providers/totp>`
* ``scheb/2fa-google-authenticator`` :doc:`Google Authenticator </providers/google>`
* ``scheb/2fa-email`` :doc:`Code-via-Email authentication </providers/email>`

Step 7: Detailed configuration
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

You probably want to configure some details of the bundle. See the :doc:`all configuration options </configuration>`.
