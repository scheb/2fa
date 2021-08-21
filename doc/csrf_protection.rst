CSRF Protection
===============

To prevent CSRF attacks on the two-factor authentication form, you can enable CSRF protection the same way you would do
it on the login form.

First, make sure that the CSRF protection is enabled in the main configuration file:

.. code-block:: yaml

   # config/packages/framework.yaml
   framework:
       csrf_protection: ~

Then, in the firewall's ``two_factor`` security configuration need to enable CSRF:

.. code-block:: yaml

   # config/packages/security.yaml
   security:
       firewalls:
           your_firewall_name:
               two_factor:
                   enable_csrf: true

Make sure you add the extra field for the CSRF token in the authentication form. The code from the default template will
do the job:

.. code-block:: html

   {% if isCsrfProtectionEnabled %}
       <input type="hidden" name="{{ csrfParameterName }}" value="{{ csrf_token(csrfTokenId) }}">
   {% endif %}

You can change the name of the field by setting ``csrf_parameter`` and change the token ID by setting ``csrf_token_id``
in your configuration:

.. code-block:: yaml

   # config/packages/security.yaml
   security:
       firewalls:
           your_firewall_name:
               two_factor:
                   enable_csrf: true
                   csrf_parameter: _csrf_security_token
                   csrf_token_id: a_private_string
