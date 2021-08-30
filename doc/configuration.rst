Configuration
=============

This is an overview of all the configuration options available:

Bundle Configuration
--------------------

.. code-block:: yaml

   # config/packages/scheb_2fa.yaml
   scheb_two_factor:

       # Trusted device feature
       trusted_device:
           enabled: false                 # If the trusted device feature should be enabled
           manager: acme.custom_trusted_device_manager  # Use a custom trusted device manager
           lifetime: 5184000              # Lifetime of the trusted device token
           extend_lifetime: false         # Automatically extend lifetime of the trusted cookie on re-login
           cookie_name: trusted_device    # Name of the trusted device cookie
           cookie_secure: false           # Set the 'Secure' (HTTPS Only) flag on the trusted device cookie
           cookie_same_site: "lax"        # The same-site option of the cookie, can be "lax", "strict" or null
           cookie_domain: ".example.com"  # Domain to use when setting the cookie, fallback to the request domain if not set
           cookie_path: "/"               # Path to use when setting the cookie

       # Backup codes feature
       backup_codes:
           enabled: false                 # If the backup code feature should be enabled
           manager: acme.custom_backup_code_manager  # Use a custom backup code manager

       # Email authentication config
       email:
           enabled: true                  # If email authentication should be enabled, default false
           mailer: acme.custom_mailer_service  # Use alternative service to send the authentication code
           code_generator: acme.custom_code_generator_service  # Use alternative service to generate authentication code
           sender_email: me@example.com   # Sender email address
           sender_name: John Doe          # Sender name
           digits: 4                      # Number of digits in authentication code
           template: security/2fa_form.html.twig   # Template used to render the authentication form
           form_renderer: acme.custom_form_renderer  # Use a custom form renderer service

       # Google Authenticator config
       google:
           enabled: true                  # If Google Authenticator should be enabled, default false
           server_name: Server Name       # Server name used in QR code
           issuer: Issuer Name            # Issuer name used in QR code
           digits: 6                      # Number of digits in authentication code
           window: 1                      # How many codes before/after the current one would be accepted as valid
           template: security/2fa_form.html.twig   # Template used to render the authentication form
           form_renderer: acme.custom_form_renderer  # Use a custom form renderer service

       # TOTP authentication config
       totp:
           enabled: true                  # If TOTP authentication should be enabled, default false
           server_name: Server Name       # Server name used in QR code
           issuer: Issuer Name            # Issuer name used in QR code
           window: 1                      # How many codes before/after the current one would be accepted as valid
           parameters:                    # Additional parameters added in the QR code
               image: 'https://my-service/img/logo.png'
           template: security/2fa_form.html.twig   # Template used to render the authentication form
           form_renderer: acme.custom_form_renderer  # Use a custom form renderer service

       # The service which is used to persist data in the user object. By default Doctrine is used. If your entity is
       # managed by something else (e.g. an API), you have to implement a custom persister.
       # Must implement Scheb\TwoFactorBundle\Model\PersisterInterface
       persister: acme.custom_persister

       # If your Doctrine user object is managed by a model manager, which is not the default one, you have to
       # set this option. Name of entity manager or null, which uses the default one.
       model_manager_name: ~

       # The security token classes, which trigger two-factor authentication.
       # By default the bundle only reacts to Symfony's username+password authentication. If you want to enable
       # two-factor authentication for other authentication methods, add their security token classes.
       security_tokens:
           - Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken
           - Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken

       # A list of IP addresses or netmasks, which will not trigger two-factor authentication.
       # Supports IPv4, IPv6 and IP subnet masks.
       ip_whitelist:
           - 127.0.0.1  # One IPv4
           - 192.168.0.0/16  # IPv4 subnet
           - 2001:0db8:85a3:0000:0000:8a2e:0370:7334  # One IPv6
           - 2001:db8:abcd:0012::0/64  # IPv6 subnet

       # If you want to have your own implementation to retrieve the whitelisted IPs.
       # The configuration option "ip_whitelist" becomes meaningless in that case.
       # Must implement Scheb\TwoFactorBundle\Security\TwoFactor\IpWhitelist\IpWhitelistProviderInterface
       ip_whitelist_provider: acme.custom_ip_whitelist_provider

       # If you want to exchange/extend the TwoFactorToken class, which is used by the bundle, you can have a factory
       # service providing your own implementation.
       # Must implement Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextFactoryInterface
       two_factor_token_factory: acme.custom_two_factor_token_factory

       # If you need custom conditions when to perform two-factor authentication.
       # Must implement Scheb\TwoFactorBundle\Security\TwoFactor\Condition\TwoFactorConditionInterface
       two_factor_condition: acme.custom_two_factor_condition

Firewall Configuration
----------------------

.. code-block:: yaml

   # config/packages/security.yaml
   security:
       firewalls:
           your_firewall_name:
               # ...
               two_factor:
                   auth_form_path: /2fa                  # Path or route name of the two-factor form
                   check_path: /2fa_check                # Path or route name of the two-factor code check
                   post_only: true                       # If the check_path should accept the code only as a POST request
                   default_target_path: /                # Where to redirect by default after successful authentication
                   always_use_default_target_path: false # If it should always redirect to default_target_path
                   auth_code_parameter_name: _auth_code  # Name of the parameter for the two-factor authentication code
                                                         # (supports symfony/property-access notation for nested values)
                   trusted_parameter_name: _trusted      # Name of the parameter for the trusted device option
                                                         # (supports symfony/property-access notation for nested values)
                   multi_factor: false                   # If ALL active two-factor methods need to be fulfilled
                                                         # (multi-factor authentication)
                   success_handler: acme.custom_success_handler  # Use a custom success handler instead of the default one
                   failure_handler: acme.custom_failure_handler  # Use a custom failure handler instead of the default one

                   # Use a custom authentication required handler instead of the default one
                   # This can be used to modify the default behavior of the bundle, which is always redirecting to the
                   # two-factor authentication form, when two-factor authentication is required.
                   authentication_required_handler: acme.custom_auth_reqired_handler

                   # Some two-factor providers need to be "prepared", usually a code is generated and sent to the user. Per
                   # default, this happens when the two-factor form is shown. But you may want to execute preparation
                   # earlier in the user's journey.
                   prepare_on_login: false          # If the two-factor provider should be prepared right after login
                   prepare_on_access_denied: false  # The the two-factor provider should be prepared when the user has to
                                                    # to complete two-factor authentication to view a page. This would
                                                    # prepare right before redirecting to the two-factor form.

                   enable_csrf: true                # If CSRF protection should be enabled on the two-factor auth form
                   csrf_parameter: _csrf_token      # The default CSRF parameter name
                                                    # (supports symfony/property-access notation for nested values)
                   csrf_token_id: two_factor        # The default CSRF token id, for generating the token value, it is
                                                    # advised to use a different id per firewall

                   # If you have multiple user providers registered, Symfony's security extension requires you to configure
                   # a user provider. You're forced to configure this node, although it doesn't have any effect on the
                   # TwoFactorBundle. So set this to any of your user providers, it doesn't matter which one.
                   provider: any_user_provider

Two-Factor Authentication Provider Configuration
------------------------------------------------

For detailed information on the authentication methods see the individual documentation:

* :doc:`TOTP </providers/totp>`
* :doc:`Google Authenticator </providers/google>`
* :doc:`Code-via-Email authentication </providers/email>`
