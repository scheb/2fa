Webauthn Authentication
=======================

Webauthn authentication uses the [W3C Webauthn API](https://www.w3.org/TR/webauthn-1/) to enable the creation and use of
strong, attested, scoped, public key-based credentials by web applications.
This method tends to replace username/password tuple for web application authentication.
It is available on multiple platforms such as Windows, MacOS or Android.
Communication with the device is possible through embedded chips (*Trusted Platform Module*), Bluetooth LE, NFC or USB.

Several parameters shall be set:

- The credential repository
-

## How authentication works

The user has to link their account to the TOTP first. This is done by generating a shared secret code, which is stored
in the user entity. Users add the code to the TOTP app either by manually typing it in together with additional
properties to configure the TOTP algorithm, or by scanning a QR which automatically transfers the information.

On successful authentication the bundle checks if there is a secret stored in the user entity. If that's the case, it
will ask for the authentication code. The user must enter the code currently shown in the TOTP app to gain access.

## Installation

To make use of this feature, you have to install `scheb/2fa-webauthn`.

```bash
composer require scheb/2fa-webauthn
```

## Basic Configuration

To enable this authentication method add this to your configuration:

```yaml
scheb_two_factor:
    webauthn:
        enabled: true
```

Your user entity has to implement `Scheb\TwoFactorBundle\Model\Webauthn\TwoFactorInterface`. To activate this method for a
user, generate a secret and define the TOTP configuration. TOTP let's you configure the number of digits, the algorithm
and the period of the temporary codes.

**We warned, custom configurations will not be compatible with the defaults of Google Authenticator app any more. You will
have to use another application (e.g. FreeOTP on Android).**

```php
<?php

namespace Acme\Demo\Entity;

use Doctrine\ORM\Mapping as ORM;
use Scheb\TwoFactorBundle\Model\Webauthn\WebauthnTwoFactorInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
* @method string getUserIdentifier()
*/class User implements UserInterface, WebauthnTwoFactorInterface
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

    public function isWebauthnAuthenticationEnabled() : bool
    {
    }

    public function getWebauthnUsername() : string
    {
        return $this->username;
    }

    public function getWebauthnUserId() : string
    {
        return $this->getId();
    }

    public function getWebauthnDisplayName() : string
    {
        return $this->userDisplayName;
    }

    public function getWebauthnIcon() : ?string
    {
        return null;
    }
}
```

## Configuration Options

```yaml
scheb_two_factor:
    webauthn:
        enabled: true                  # If TOTP authentication should be enabled, default false
        server_id: example.com         # The ID of the server. In the Webauthn context, it corresponds to the domain
        server_name: Server Name       # Server name used in QR code
```

## Custom Authentication Form Template

The bundle uses `Resources/views/Authentication/form.html.twig` to render the authentication form. If you want to use a
different template you can simply register it in configuration:

```yaml
scheb_two_factor:
    webauthn:
        template: security/2fa_form.html.twig
```

## Custom Form Rendering

There are certain cases when it's not enough to just change the template. For example, you're using two-factor
authentication on multiple firewalls and you need to
[render the form differently for each firewall](../firewall_template.md). In such a case you can implement a form
renderer to fully customize the rendering logic.

Create a class implementing `Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorFormRendererInterface`:

```php
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
```

Then register it as a service and update your configuration:

```yaml
# config/packages/scheb_two_factor.yaml
scheb_two_factor:
    webauthn:
        form_renderer: acme.custom_form_renderer_service
```

## Authenticator Registration
