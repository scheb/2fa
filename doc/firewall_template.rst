Different Template per Firewall
===============================

You're using two-factor authentication on multiple firewalls and you need to render the form differently for each
firewall. Here's a basic solution for you:

Create a new form renderer class like this:

.. code-block:: php

   <?php

   namespace Acme\Demo;

   use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorFormRendererInterface;
   use Symfony\Component\HttpFoundation\Request;
   use Symfony\Component\HttpFoundation\Response;
   use Symfony\Component\Security\Http\FirewallMapInterface;
   use Twig\Environment;

   class CustomFormRenderer implements TwoFactorFormRendererInterface
   {
       public function __construct(
           private Environment $twigEnvironment,
           private FirewallMapInterface $firewallMap,
           private array $templates, // Map of [firewall name => template path]
       ) {
       }

       public function renderForm(Request $request, array $templateVars): Response
       {
           $firewallName = $this->firewallMap->getFirewallConfig($request)->getName();
           $template = $this->templates[$firewallName];

           $content = $this->twigEnvironment->render($template, $templateVars);
           $response = new Response();
           $response->setContent($content);

           return $response;
       }
   }

Register it as a service:

.. code-block:: yaml

   # config/services.yaml
   services:
       acme.custom_form_renderer:
           class: Acme\Demo\CustomFormRenderer
           arguments:
               - '@twig'
               - '@security.firewall.map'
               # This is a map of firewall name to template path
               - { main: 'security/2fa_google.html.twig', admin: 'admin/security/2fa_google.html.twig' } ]

Configure the new service as the form renderer:

.. code-block:: yaml

   # config/packages/scheb_2fa.yaml
   scheb_two_factor:
       google:  # Or "totp" or "email", depending on the two-factor provider you're using
           enabled: true
           form_renderer: acme.custom_form_renderer
