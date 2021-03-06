Google Authenticator
====================

[Google Authenticator](https://en.wikipedia.org/wiki/Google_Authenticator) is a popular implementation of a
[TOTP algorithm](https://en.wikipedia.org/wiki/Time-based_One-Time_Password) to generate authentication codes. Compared
to the [TOTP two-factor provider](totp.md), the implementation has a fixed configuration, which is necessary to be
compatible with the Google Authenticator app:

- it generates 6-digit codes
- the code changes every 30 seconds

If you need different settings, please use the [TOTP two-factor provider](totp.md). Be warned that custom TOTP
configurations likely won't be compatible with the Google Authenticator app.

## How authentication works

The user has to link their account to the Google Authenticator app first. This is done by generating a shared secret
code, which is stored in the user entity. Users add the code to the Google Authenticator app either by manually typing
it in, or scanning a QR which automatically transfers the information.

On successful authentication the bundle checks if there is a secret stored in the user entity. If that's the case, it
will ask for the authentication code. The user must enter the code currently shown in the Google Authenticator app to
gain access.

For more information see the [Google Authenticator website](http://code.google.com/p/google-authenticator/).

## Installation

To make use of this feature, you have to install `scheb/2fa-google-authenticator`.

```bash
composer require scheb/2fa-google-authenticator
```

## Basic Configuration

To enable this authentication method add this to your configuration:

```yaml
# config/packages/scheb_two_factor.yaml
scheb_two_factor:
    google:
        enabled: true
```

Your user entity has to implement `Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface`. To activate Google
Authenticator for a user, generate a secret code and persist it with the user entity.

```php
<?php

namespace Acme\DemoBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class User implements UserInterface, TwoFactorInterface
{
    /**
     * @ORM\Column(name="googleAuthenticatorSecret", type="string", nullable=true)
     */
    private $googleAuthenticatorSecret;

    // [...]

    public function isGoogleAuthenticatorEnabled(): bool
    {
        return $this->googleAuthenticatorSecret ? true : false;
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
```

## Configuration Reference

```yaml
# config/packages/scheb_two_factor.yaml
scheb_two_factor:
    google:
        enabled: true                  # If Google Authenticator should be enabled, default false
        server_name: Server Name       # Server name used in QR code
        issuer: Issuer Name            # Issuer name used in QR code
        digits: 6                      # Number of digits in authentication code
        window: 1                      # How many codes before/after the current one would be accepted as valid
        template: security/2fa_form.html.twig   # Template used to render the authentication form
```

## Custom Authentication Form Template

The bundle uses `Resources/views/Authentication/form.html.twig` to render the authentication form. If you want to use a
different template you can simply register it in configuration:

```yaml
# config/packages/scheb_two_factor.yaml
scheb_two_factor:
    google:
        template: security/2fa_form.html.twig
```

## Custom Form Rendering

There are certain cases when it's not enough to just change the template. For example, you're using two-factor
authentication on multiple firewalls and you need to render the form differently for each firewall. In such a case you
can implement a form renderer to fully customize the rendering logic.

Create a class implementing `Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorFormRendererInterface`:

```php
<?php

namespace Acme\DemoBundle\FormRenderer;

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
```

Then register it as a service and update your configuration:

```yaml
# config/packages/scheb_two_factor.yaml
scheb_two_factor:
    google:
        form_renderer: acme.custom_form_renderer_service
```

## Generating a Secret Code

The service `scheb_two_factor.security.google_authenticator` provides a method to generate new secret for Google
Authenticator. Auto-wiring of `Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface`
is also possible.

```php
$secret = $container->get("scheb_two_factor.security.google_authenticator")->generateSecret();
```

## QR Codes

To generate a QR code that can be scanned by the Google Authenticator app, retrieve the QR code's content from Google
Authenticator service:

```php
$qrCodeContent = $container->get("scheb_two_factor.security.google_authenticator")->getQRContent($user);
```

To render the QR code as an image, install `scheb/2fa-qr-code`:

```bash
composer require scheb/2fa-qr-code
```

Use service `scheb_two_factor.qr_code_generator` to get the QR code image. Auto-wiring of
`Scheb\TwoFactorBundle\Security\TwoFactor\QrCode\QrCodeGenerator` is also possible. You need to implement a small
controller to display the image in your application.

```php
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
        $qrCode = $qrCodeGenerator->getGoogleAuthenticatorQrCode($this->getUser());

        return new Response($qrCode->writeString(), 200, ['Content-Type' => 'image/png']);
    }
}
```

**Security note:** Keep the QR code content within your application. Render the image yourself. Do not pass the content
to an external service, because this is exposing the secret code to that service.
