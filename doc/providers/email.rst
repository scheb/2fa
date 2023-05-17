Code-via-Email Authentication
=============================

A two-factor provider to generate a random numeric code and send it to the user via email.

How authentication works
------------------------

On successful authentication it generates a random number and persists it in the user entity. The number is sent to the
user via email. Then the user must enter that number to gain access.

The number of digits can be configured:

.. code-block:: yaml

   # config/packages/scheb_2fa.yaml
   scheb_two_factor:
       email:
           digits: 6

Installation
------------

To make use of this feature, you have to install ``scheb/2fa-email``.

.. code-block:: bash

   composer require scheb/2fa-email

The bundle's default implementation for sending emails assumes you're using ``symfony/mailer``. To install the package:

.. code-block:: bash

   composer require symfony/mailer

You're free to use any other mail-sending library you like, but then you *have* to implement a custom mailer class
(instructions below).

Basic Configuration
-------------------

To enable this authentication method add this to your configuration:

.. code-block:: yaml

   # config/packages/scheb_2fa.yaml
   scheb_two_factor:
       email:
           enabled: true
           sender_email: no-reply@example.com
           sender_name: John Doe  # Optional
           subject_email: Authentication Code # Email subject

Your user entity has to implement ``Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface``. The authentication code must
be persisted, so make sure that it is stored in a persisted field.

.. configuration-block::

    .. code-block:: php-annotations

       <?php

       namespace Acme\Demo\Entity;

       use Doctrine\ORM\Mapping as ORM;
       use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;
       use Symfony\Component\Security\Core\User\UserInterface;

       class User implements UserInterface, TwoFactorInterface
       {
           /**
            * @ORM\Column(type="string")
            */
           private string $email;

           /**
            * @ORM\Column(type="string", nullable=true)
            */
           private ?string $authCode;

           // [...]

           public function isEmailAuthEnabled(): bool
           {
               return true; // This can be a persisted field to switch email code authentication on/off
           }

           public function getEmailAuthRecipient(): string
           {
               return $this->email;
           }

           public function getEmailAuthCode(): string
           {
               if (null === $this->authCode) {
                   throw new \LogicException('The email authentication code was not set');
               }

               return $this->authCode;
           }

           public function setEmailAuthCode(string $authCode): void
           {
               $this->authCode = $authCode;
           }
       }

    .. code-block:: php-attributes

       <?php

       namespace Acme\Demo\Entity;

       use Doctrine\ORM\Mapping as ORM;
       use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;
       use Symfony\Component\Security\Core\User\UserInterface;

       class User implements UserInterface, TwoFactorInterface
       {
           #[ORM\Column(type: 'string')]
           private string $email;

           #[ORM\Column(type: 'string', nullable: true)]
           private ?string $authCode;

           // [...]

           public function isEmailAuthEnabled(): bool
           {
               return true; // This can be a persisted field to switch email code authentication on/off
           }

           public function getEmailAuthRecipient(): string
           {
               return $this->email;
           }

           public function getEmailAuthCode(): string
           {
               if (null === $this->authCode) {
                   throw new \LogicException('The email authentication code was not set');
               }

               return $this->authCode;
           }

           public function setEmailAuthCode(string $authCode): void
           {
               $this->authCode = $authCode;
           }
       }

Configuration Reference
-----------------------

.. code-block:: yaml

   # config/packages/scheb_2fa.yaml
   scheb_two_factor:
       email:
           enabled: true                  # If email authentication should be enabled, default false
           mailer: acme.custom_mailer_service  # Use alternative service to send the authentication code
           code_generator: acme.custom_code_generator_service  # Use alternative service to generate authentication code
           sender_email: me@example.com   # Sender email address
           sender_name: John Doe          # Sender name
           subject_email: Authentication Code # Email subject
           digits: 4                      # Number of digits in authentication code
           template: security/2fa_form.html.twig   # Template used to render the authentication form

Custom Mailer
-------------

By default the email is plain text and very simple. If you want a different style (e.g. HTML) you have to create your
own mailer service. It must implement ``Scheb\TwoFactorBundle\Mailer\AuthCodeMailerInterface``.

.. code-block:: php

   <?php

   namespace Acme\Demo\Mailer;

   use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;
   use Scheb\TwoFactorBundle\Mailer\AuthCodeMailerInterface;

   class MyAuthCodeMailer implements AuthCodeMailerInterface
   {
       // [...]

       public function sendAuthCode(TwoFactorInterface $user): void
       {
           $authCode = $user->getEmailAuthCode();

           // Send email
       }
   }

Then register it as a service and update your configuration:

.. code-block:: yaml

   # config/packages/scheb_2fa.yaml
   scheb_two_factor:
       email:
           mailer: acme.custom_mailer_service

Re-send Authentication Code
---------------------------

When you're using the default authentication code generator that is coming with the bundle, there's an easy way to
resend the email with the authentication code. Get/inject service ``scheb_two_factor.security.email.code_generator``
and call method ``reSend(\Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface $user)``.

Custom Code Generator
---------------------

If you want to have the code generated differently, you can have your own code generator. Create a service implementing
``Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Email\Generator\CodeGeneratorInterface`` and register it in the
configuration:

.. code-block:: yaml

   # config/packages/scheb_2fa.yaml
   scheb_two_factor:
       email:
           code_generator: acme.custom_code_generator_service

Custom Authentication Form Template
-----------------------------------

The bundle uses ``Resources/views/Authentication/form.html.twig`` to render the authentication form. If you want to use
a different template you can simply register it in configuration:

.. code-block:: yaml

   # config/packages/scheb_2fa.yaml
   scheb_two_factor:
       email:
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
       email:
           form_renderer: acme.custom_form_renderer_service
