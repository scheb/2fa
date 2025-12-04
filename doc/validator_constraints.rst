Validating Two-Factor Codes in Forms
=====================================

When building forms that require users to enter a two-factor authentication code, you can use the built-in validation
constraints provided by the bundle to automatically verify the code against the authenticator provider.

TOTP Code Validation
--------------------

Use the ``UserTotpCode`` constraint when you want to validate codes from the **TOTP** authentication provider.

.. code-block:: php

    use Scheb\TwoFactorBundle\Security\TwoFactor\Validator\Constraints\UserTotpCode;
    use Symfony\Component\Validator\Constraints as Assert;

    class SecuritySettingsForm
    {
        #[Assert\NotBlank(message: 'Please enter your authentication code')]
        #[UserTotpCode(message: 'The authentication code is invalid')]
        public string $totpCode;
    }

Google Authenticator Code Validation
------------------------------------

Use the ``UserGoogleTotpCode`` constraint when you want to validate codes from the **Google Authenticator** provider.

.. code-block:: php

    use Scheb\TwoFactorBundle\Security\TwoFactor\Validator\Constraints\UserGoogleTotpCode;
    use Symfony\Component\Validator\Constraints as Assert;

    class LoginForm
    {
        #[Assert\NotBlank(message: 'Please enter your authentication code')]
        #[UserTotpCode(message: 'The authentication code is invalid')]
        public string $authCode;
    }
