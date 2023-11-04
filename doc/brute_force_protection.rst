Brute Force Protection
======================

Brute force protection is essential for two-factor authentication, because otherwise the authentication code could just
be guessed by an attacker.

Login Throttling
----------------

In Symfony 5.2 "login throttling" was introduced as a feature to Symfony's security system. If you active this feature
on the firewall settings, you'll automatically have brute force protection for login *and* two-factor authentication.

.. code-block:: yaml

   # config/packages/security.yaml
       security:
           firewalls:
               your_firewall_name:
                   login_throttling:
                       max_attempts: 3
                       interval: '15 minutes'

Please see `Symfony Security Bundle documentation <https://symfony.com/doc/current/security.html#limiting-login-attempts>`_
for details on this feature and its configuration.

Custom Implementation
---------------------

If you need a custom implementation for brute force protection, you can easily implement one by listening to the
:doc:`events </events>` provided by the bundle.

**1) Log failed two-factor attempts**

Register a listener for the ``scheb_two_factor.authentication.failure`` event. Log whatever you need (IP, user, etc.)
to detect brute force attacks.

**2) Block authentication**

Register a listener for the ``scheb_two_factor.authentication.attempt`` event. Execute your brute-force detection logic
and decide if the attempt should be blocked. Since that event is dispatched directly before the two-factor code is
checked, you can prevent that from happening by throwing a new exception of type
``Symfony\Component\Security\Core\Exception\AuthenticationException``. That exception will be caught by the
authentication layer and the exception message is shown to the user.
