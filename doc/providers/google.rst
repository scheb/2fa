Google Authenticator
====================

`Google Authenticator <https://en.wikipedia.org/wiki/Google_Authenticator>`_ is a popular implementation of a
`TOTP algorithm <https://en.wikipedia.org/wiki/Time-based_One-Time_Password>`_ to generate authentication codes.
Compared to the :doc:`TOTP two-factor provider </providers/totp>`, the implementation has a fixed configuration, which is
necessary to be compatible with the Google Authenticator app:

* it generates 6-digit codes
* the code changes every 30 seconds
* It uses the sha1 hashing algorithm

If you need different settings, please use the :doc:`TOTP two-factor provider </providers/totp>`. Be warned that custom TOTP
configurations likely won't be compatible with the Google Authenticator app.

How authentication works
------------------------

The user has to link their account to the Google Authenticator app first. This is done by generating a shared secret
code, which is stored in the user entity. Users add the code to the Google Authenticator app either by manually typing
it in, or scanning a QR which automatically transfers the information.

On successful authentication the bundle checks if there is a secret stored in the user entity. If that's the case, it
will ask for the authentication code. The user must enter the code currently shown in the Google Authenticator app to
gain access.

For more information see the `Google Authenticator website <https://github.com/google/google-authenticator/wiki>`_.

Installation
------------

To make use of this feature, you have to install ``scheb/2fa-google-authenticator``.

.. code-block:: bash

   composer require scheb/2fa-google-authenticator

Basic Configuration
-------------------

To enable this authentication method add this to your configuration:

.. code-block:: yaml

   # config/packages/scheb_2fa.yaml
   scheb_two_factor:
       google:
           enabled: true

Your user entity has to implement ``Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface``. To activate Google
Authenticator for a user, generate a secret code and persist it with the user entity.

.. configuration-block::

    .. code-block:: php-annotations

       <?php

       namespace Acme\Demo\Entity;

       use Doctrine\ORM\Mapping as ORM;
       use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface;
       use Symfony\Component\Security\Core\User\UserInterface;

       class User implements UserInterface, TwoFactorInterface
       {
           /**
            * @ORM\Column(type="string", nullable=true)
            */
           private ?string $googleAuthenticatorSecret;

           // [...]

           public function isGoogleAuthenticatorEnabled(): bool
           {
               return null !== $this->googleAuthenticatorSecret;
           }

           public function getGoogleAuthenticatorUsername(): string
           {
               return $this->username;
           }

           public function getGoogleAuthenticatorSecret(): ?string
           {
               return $this->googleAuthenticatorSecret;
           }

           public function setGoogleAuthenticatorSecret(?string $googleAuthenticatorSecret): void
           {
               $this->googleAuthenticatorSecret = $googleAuthenticatorSecret;
           }
       }

    .. code-block:: php-attributes

        <?php

        namespace Acme\Demo\Entity;

        use Doctrine\ORM\Mapping as ORM;
        use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface;
        use Symfony\Component\Security\Core\User\UserInterface;

        class User implements UserInterface, TwoFactorInterface
        {
           #[ORM\Column(type: 'string', nullable: true)]
           private ?string $googleAuthenticatorSecret;

           // [...]

           public function isGoogleAuthenticatorEnabled(): bool
           {
               return null !== $this->googleAuthenticatorSecret;
           }

           public function getGoogleAuthenticatorUsername(): string
           {
               return $this->username;
           }

           public function getGoogleAuthenticatorSecret(): ?string
           {
               return $this->googleAuthenticatorSecret;
           }

           public function setGoogleAuthenticatorSecret(?string $googleAuthenticatorSecret): void
           {
               $this->googleAuthenticatorSecret = $googleAuthenticatorSecret;
           }
        }

Configuration Reference
-----------------------

.. code-block:: yaml

   # config/packages/scheb_2fa.yaml
   scheb_two_factor:
       google:
           enabled: true                  # If Google Authenticator should be enabled, default false
           server_name: Server Name       # Server name used in QR code
           issuer: Issuer Name            # Issuer name used in QR code
           digits: 6                      # Number of digits in authentication code
           window: 1                      # [DEPRECATED since v6.11, will be removed in v7] Use "leeway", if possible
                                          # Behavior depends on the version of Spomky-Labs/otphp used:
                                          # - Until v10: How many codes before/after the current one would be accepted
                                          # - From v11: Acceptable time drift in seconds
           leeway: 0                      # Acceptable time drift in seconds, requires Spomky-Labs/otphp v11 to be used
                                          # Must be less or equal than 30 seconds
                                          # If configured, takes precedence over the "window" option
           template: security/2fa_form.html.twig   # Template used to render the authentication form

Custom Authentication Form Template
-----------------------------------

The bundle uses ``Resources/views/Authentication/form.html.twig`` to render the authentication form. If you want to use
a different template you can simply register it in configuration:

.. code-block:: yaml

   # config/packages/scheb_2fa.yaml
   scheb_two_factor:
       google:
           template: security/2fa_form.html.twig

Custom Form Rendering
---------------------

There are certain cases when it's not enough to just change the template. For example, you're using two-factor
authentication on multiple firewalls and you need to
:doc:`render the form differently for each firewall </firewall_template>`. In such a case you can implement a form
renderer to fully customize the rendering logic.

Create a class implementing ``Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorFormRendererInterface``:

.. code-block:: php

   <?php

   namespace Acme\Demo\FormRenderer;

   use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorFormRendererInterface;
   use Symfony\Component\HttpFoundation\Request;
   use Symfony\Component\HttpFoundation\Response;

   class MyFormRenderer implements TwoFactorFormRendererInterface
   {
       // [...]

       public function renderForm(Request $request, array $templateVars): Response
       {
           // Customize form rendering
       }
   }

Then register it as a service and update your configuration:

.. code-block:: yaml

   # config/packages/scheb_2fa.yaml
   scheb_two_factor:
       google:
           form_renderer: acme.custom_form_renderer_service

Generating a Secret Code
------------------------

The service ``scheb_two_factor.security.google_authenticator`` provides a method to generate new secret for Google
Authenticator. Auto-wiring of ``Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface``
is also possible.

.. code-block:: php

   $secret = $container->get("scheb_two_factor.security.google_authenticator")->generateSecret();

QR Codes
--------

To generate a QR code that can be scanned by the Google Authenticator app, retrieve the QR code's content from Google
Authenticator service:

.. code-block:: php

   $qrCodeContent = $container->get("scheb_two_factor.security.google_authenticator")->getQRContent($user);

Use the QR code rendering library of your choice to render a QR code image.

An example how to render the QR code with ``endroid/qr-code`` version 4 can be found
`in the demo application <https://github.com/scheb/2fa/blob/6.x/app/src/Controller/QrCodeController.php>`_.

.. caution::

    **Security note:** Keep the QR code content within your application. Render the image yourself. Do not pass the
    content to an external service, because this is exposing the secret code to that service.
