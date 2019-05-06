Google Authentication
====================

## How it works

The user entity has to be linked with Google Authenticator first. This is done by generating a secret code and storing
it in the user entity. Users can add that code to the Google Authenticator app on their mobile. The app will generate a
6-digit numeric code from it that changes every 30 seconds.

On successful authentication the bundle checks if there is a secret stored in the user entity. If that's the case it
will ask for the authentication code. The user must enter the code currently shown in the Google Authenticator app to
gain access.

For more information see the [Google Authenticator website](http://code.google.com/p/google-authenticator/).


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

## Generating a Secret Code

The service `scheb_two_factor.security.google_authenticator` provides a method to generate new secret for Google
Authenticator.

```php
$secret = $container->get("scheb_two_factor.security.google_authenticator")->generateSecret();
```

With Symfony 4, you can use auto-wiring dependency injection to get the services:

```php
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface;

public function generateSecret(GoogleAuthenticatorInterface $googleAuthenticatorService)
{
    $secret = $googleAuthenticatorService->generateSecret();
}
```

## QR Codes

To generate a QR code that can be scanned by the Google Authenticator app, retrieve the QR code's content from Google
Authenticator service:

```php
$qrCodeContent = $container->get("scheb_two_factor.security.google_authenticator")->getQRContent($user);
```

Use a library such as [endroid/qr-code-bundle](https://github.com/endroid/qr-code-bundle) or one of the many JavaScript
libraries to render the QR code image.

**Security note:** Keep the QR code content within your application. Render the image yourself. Do not pass the content
to an external service, because this is exposing the secret code to that service.
