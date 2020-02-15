Email Authentication
====================

## Prerequisites

Install the mailer component:

```bash
composer require mailer
```

## How it works

On successful authentication it generates a random number and persist it in the user entity. The number is sent to the
user via email. Then the user must enter the right number to gain access.

The number of digits can be configured:

```yaml
# config/packages/scheb_two_factor.yaml
scheb_two_factor:
    email:
        digits: 6
```

## Basic Configuration

To enable this authentication method add this to your configuration:

```yaml
# config/packages/scheb_two_factor.yaml
scheb_two_factor:
    email:
        enabled: true
        sender_email: no-reply@example.com
        sender_name: John Doe  # Optional
```

Your user entity has to implement `Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface`. The authentication code must
be persisted, so make sure that it is stored in a persisted field.

```php
namespace Acme\DemoBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class User implements UserInterface, TwoFactorInterface
{
    /**
     * @ORM\Column(type="string")
     */
    private $email;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $authCode;

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
        return $this->authCode;
    }

    public function setEmailAuthCode(string $authCode): void
    {
        $this->authCode = $authCode;
    }
}
```

## Configuration Reference

```yaml
# config/packages/scheb_two_factor.yaml
scheb_two_factor:
    email:
        enabled: true                  # If email authentication should be enabled, default false
        mailer: acme.custom_mailer_service  # Use alternative service to send the authentication code
        code_generator: acme.custom_code_generator_service  # Use alternative service to generate authentication code
        sender_email: me@example.com   # Sender email address
        sender_name: John Doe          # Sender name
        digits: 4                      # Number of digits in authentication code
        template: security/2fa_form.html.twig   # Template used to render the authentication form
```

## Custom Mailer

By default the email is plain text and very simple. If you want a different style (e.g. HTML) you have to create your
own mailer service. It must implement `Scheb\TwoFactorBundle\Mailer\AuthCodeMailerInterface`.

```php
namespace Acme\DemoBundle\Mailer;

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
```

Then register it as a service and update your configuration:

```yaml
# config/packages/scheb_two_factor.yaml
scheb_two_factor:
    email:
        mailer: acme.custom_mailer_service
```

## Symfony Mailer Component

The default mailer service uses Swift Mailer to send the email. To use the Symfony Mailer component instead (without
creating your own custom mailer service) simply update your configuration as follows:

```yaml
# config/packages/scheb_two_factor.yaml
scheb_two_factor:
    email:
        mailer: scheb_two_factor.security.email.symfony_auth_code_mailer
```

## Custom Code Generator

If you want to have the code generated differently, you can have your own code generator. Create a service implementing
`Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Email\Generator\CodeGeneratorInterface` and register it in the
configuration:

```yaml
# config/packages/scheb_two_factor.yaml
scheb_two_factor:
    email:
        code_generator: acme.custom_code_generator_service
```

## Custom Authentication Form Template

The bundle uses `Resources/views/Authentication/form.html.twig` to render the authentication form. If you want to use a
different template you can simply register it in configuration:

```yaml
# config/packages/scheb_two_factor.yaml
scheb_two_factor:
    email:
        template: security/2fa_form.html.twig
```
