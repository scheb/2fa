Custom Conditions for Two-Factor Authentication
===============================================

In your application, you may have extra requirements when to perform two-factor authentication, which goes beyond what
the bundle is doing automatically. In such a case you need to implement
``\Scheb\TwoFactorBundle\Security\TwoFactor\Condition\TwoFactorConditionInterface``:

.. code-block:: php

   <?php

   use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
   use Scheb\TwoFactorBundle\Security\TwoFactor\Condition\TwoFactorConditionInterface;

   class MyTwoFactorCondition implements TwoFactorConditionInterface
   {
       public function shouldPerformTwoFactorAuthentication(AuthenticationContextInterface $context): bool
       {
           // Your conditions here
       }
   }

Register it as a service and configure the service name:

.. code-block:: yaml

   # config/packages/scheb_2fa.yaml
   scheb_two_factor:
       two_factor_condition: acme.custom_two_factor_condition

Bypassing Two-Factor Authentication
-----------------------------------

If you simply wish to bypass 2fa for a specific authenticator, setting the
``TwoFactorAuthenticator::FLAG_2FA_COMPLETE`` attribute on the security token will achieve this.

For example, if you are building a `custom Authenticator <https://symfony.com/doc/current/security/custom_authenticator.html>`_
this would bypass 2fa when the authenticator is used:

.. code-block:: php

   <?php

   namespace Acme\Demo;

   use Scheb\TwoFactorBundle\Security\Http\Authenticator\TwoFactorAuthenticator;
   use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
   use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
   use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

   class MyAuthenticator extends AbstractAuthenticator
   {
       public function createToken(Passport $passport, string $firewallName): TokenInterface
       {
           $token = parent::createAuthenticatedToken($passport, $firewallName);

           // Set this to bypass 2fa for this authenticator
           $token->setAttribute(TwoFactorAuthenticator::FLAG_2FA_COMPLETE, true);

           return $token;
       }

       // ...
   }
