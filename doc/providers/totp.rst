TOTP Authentication
===================

TOTP authentication uses the `TOTP algorithm <https://en.wikipedia.org/wiki/Time-based_One-Time_Password>`_ to generate
authentication codes. Compared to :doc:`Google Authenticator two-factor provider </google>`, the TOTP two-factor provider
offers more configuration options, but that means your configuration isn't necessarily compatible with the
`Google Authenticator app <http://code.google.com/p/google-authenticator/>`_.

Several parameters can be customized:


* The number of digits (default = ``6``)
* The digest (default = ``sha1``)
* The period (default = ``30`` seconds)
* Custom parameters can be added

ℹ️ Use the default values to configure TOTP compatible with Google Authenticator (6 digits, sha1 algorithm, 30 seconds
period).

How authentication works
------------------------

The user has to link their account to the TOTP first. This is done by generating a shared secret code, which is stored
in the user entity. Users add the code to the TOTP app either by manually typing it in together with additional
properties to configure the TOTP algorithm, or by scanning a QR which automatically transfers the information.

On successful authentication the bundle checks if there is a secret stored in the user entity. If that's the case, it
will ask for the authentication code. The user must enter the code currently shown in the TOTP app to gain access.

Installation
------------

To make use of this feature, you have to install ``scheb/2fa-totp``.

.. code-block:: bash

   composer require scheb/2fa-totp

Basic Configuration
-------------------

To enable this authentication method add this to your configuration:

.. code-block:: yaml

   scheb_two_factor:
       totp:
           enabled: true

Your user entity has to implement ``Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface``. To activate this method for a
user, generate a secret and define the TOTP configuration. TOTP let's you configure the number of digits, the algorithm
and the period of the temporary codes.

**We warned, custom configurations will not be compatible with the defaults of Google Authenticator app any more. You will
have to use another application (e.g. FreeOTP on Android).**

.. code-block:: php

   <?php

   namespace Acme\Demo\Entity;

   use Doctrine\ORM\Mapping as ORM;
   use Scheb\TwoFactorBundle\Model\Totp\TotpConfiguration;
   use Scheb\TwoFactorBundle\Model\Totp\TotpConfigurationInterface;
   use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface;
   use Symfony\Component\Security\Core\User\UserInterface;

   class User implements UserInterface, TwoFactorInterface
   {
       /**
        * @ORM\Column(name="totpSecret", type="string", nullable=true)
        */
       private $totpSecret;

       // [...]

       public function isTotpAuthenticationEnabled(): bool
       {
           return $this->totpSecret ? true : false;
       }

       public function getTotpAuthenticationUsername(): string
       {
           return $this->username;
       }

       public function getTotpAuthenticationConfiguration(): TotpConfigurationInterface
       {
           // You could persist the other configuration options in the user entity to make it individual per user.
           return new TotpConfiguration($this->totpSecret, TotpConfiguration::ALGORITHM_SHA1, 20, 8);
       }
   }

Configuration Options
---------------------

.. code-block:: yaml

   scheb_two_factor:
       totp:
           enabled: true                  # If TOTP authentication should be enabled, default false
           server_name: Server Name       # Server name used in QR code
           issuer: Issuer Name            # Issuer name used in QR code
           window: 1                      # How many codes before/after the current one would be accepted as valid
           parameters:                    # Additional parameters added in the QR code
               image: 'https://my-service/img/logo.png'
           template: security/2fa_form.html.twig   # Template used to render the authentication form

Additional parameter
--------------------

You can set additional parameters, that will be added to the provisioning URI, which is contained in the QR code.
Parameters will be common for all users. Custom parameters may not be supported by all applications, but can be very
interesting to customize the QR codes. In the example below, we add an ``image`` parameter with the URL to the service's
logo. Some applications, such as FreeOTP, support this parameter and will associate the QR code with that logo.

.. code-block:: yaml

   scheb_two_factor:
       totp:
           parameters:
               image: 'https://my-service/img/logo.png'

Custom Authentication Form Template
-----------------------------------

The bundle uses ``Resources/views/Authentication/form.html.twig`` to render the authentication form. If you want to use a
different template you can simply register it in configuration:

.. code-block:: yaml

   scheb_two_factor:
       totp:
           template: security/2fa_form.html.twig

Custom Form Rendering
---------------------

There are certain cases when it's not enough to just change the template. For example, you're using two-factor
authentication on multiple firewalls and you need to
:doc:`render the form differently for each firewall </../firewall_template>`. In such a case you can implement a form
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
       totp:
           form_renderer: acme.custom_form_renderer_service

Generating a Secret Code
------------------------

The service ``scheb_two_factor.security.totp_authenticator`` provides a method to generate new secret for TOTP
authentication. Auto-wiring of ``Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface`` is
also possible.

.. code-block:: php

   $secret = $container->get("scheb_two_factor.security.totp_authenticator")->generateSecret();

QR Codes
--------

To generate a QR code that can be scanned by the authenticator app, retrieve the QR code's content from TOTP service:

.. code-block:: php

   $qrCodeContent = $container->get("scheb_two_factor.security.totp_authenticator")->getQRContent($user);

To render the QR code as an image, install ``scheb/2fa-qr-code``:

.. code-block:: bash

   composer require scheb/2fa-qr-code

Use service ``scheb_two_factor.qr_code_generator`` to get the QR code image. Auto-wiring of
``Scheb\TwoFactorBundle\Security\TwoFactor\QrCode\QrCodeGenerator`` is also possible. You need to implement a small
controller to display the image in your application.

.. code-block:: php

   <?php

   namespace App\Controller;

   use Scheb\TwoFactorBundle\Security\TwoFactor\QrCode\QrCodeGenerator;
   use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
   use Symfony\Component\HttpFoundation\Response;
   use Symfony\Component\Routing\Annotation\Route;

   class QrCodeController extends AbstractController
   {
       /**
        * @Route("/qr-code", name="qr_code")
        */
       public function displayGoogleAuthenticatorQrCode(QrCodeGenerator $qrCodeGenerator)
       {
           // $qrCode is provided by the endroid/qr-code library. See the docs how to customize the look of the QR code:
           // https://github.com/endroid/qr-code
           $qrCode = $qrCodeGenerator->getTotpQrCode($this->getUser());

           return new Response($qrCode->writeString(), 200, ['Content-Type' => 'image/png']);
       }
   }

**Security note:** Keep the QR code content within your application. Render the image yourself. Do not pass the content
to an external service, because this is exposing the secret code to that service.
