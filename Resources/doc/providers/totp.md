TOTP Authentication
====================

## How it works

This authenticator is similar and compatible with [Google Authenticator](google.md).
The configuration process is identical: a secret code and parameters are associated to the user.
Users can add that code to the application on their mobile.
The app will generate a numeric code from it that changes after a period of time.

The main difference is that several parameters can be customized:

* The number of digits (default = 6),
* The digest (default = sha1),
* The period (default = 30 seconds),
* Custom parameters can be added.


## Basic Configuration

To enable this authentication method add this to your configuration:

```yaml
scheb_two_factor:
    totp:
        enabled: true
```

Your user entity has to implement `Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface`.
To activate this method for a user, generate a provisioning URI and persist it with the user entity.

```php
namespace Acme\DemoBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface;

class User implements TwoFactorInterface
{
    /**
     * @ORM\Column(name="totpAuthenticatorProvisioningUri", type="string", nullable=true)
     */
    private $totpAuthenticatorProvisioningUri;

    // [...]
    
    public function isTotpAuthenticatorEnabled(): bool
    {
        return $this->totpAuthenticatorProvisioningUri ? true : false;
    }

    public function getTotpAuthenticatorProvisioningUri(): string
    {
        return $this->totpAuthenticatorProvisioningUri;
    }

    public function setTotpAuthenticatorProvisioningUri(?string $totpAuthenticatorProvisioningUri): void
    {
        $this->totpAuthenticatorProvisioningUri = $totpAuthenticatorProvisioningUri;
    }
}
```

## Custom Parameters

You can change the number of digits, the digest and the period of the temporary codes.

Be warned that in case of modification, the generated provisioning Uri will not be compatible
with Google Authenticator any more.
You will have to use another application (e.g. FreeOTP on Android).

```yaml
scheb_two_factor:
    totp:
        digits: 8
        digest: 'sha256'
        period: 20
```

You can also set additional parameters that will be added to provisioning Uris. They will be common for all users.
Custom parameters may not be supported by the applications, but can be very intersting to customize the QR Codes.
In the example below, we add an `image` parameter with the Uri to the service logo. Some applications such as FreeOTP
support this parameter and will associate the QR Code with that logo.

```yaml
scheb_two_factor:
    totp:
        parameters:
            image: 'https://my-service/img/logo.png'
```

## Custom Authentication Form Template

The bundle uses `Resources/views/Authentication/form.html.twig` to render the authentication form. If you want to use a
different template you can simply register it in configuration:

```yaml
scheb_two_factor:
    totp:
        template: security/2fa_form.html.twig
```

## Generating a Provisioning Uri

The service `scheb_two_factor.security.totp_authenticator` provides a method to generate new Provisioning Uris for your users.
In the example below, we use the e-mail as label, but you can use any other display name you want (e.g. the username).

```php
$totp = $container->get("scheb_two_factor.security.totp_authenticator")->generateNewTotp();
$totp->setLabel(
    $user->getEmail()
);
$provisioningUri = $totp->getProvisioningUri()
```

With Symfony 4 you use the dependency injection to get the services:

```php
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;

public function index(TotpAuthenticatorInterface $twoFactor)
{
    // ...
    $totp = $twoFactor->generateNewTotp();
    // ...
}
```

## QR Codes

**Warning** To generate the QR-code an external service from Google is used. That means the user's personal secure code
is transmitted to that service. This is considered a bad security practice. If you don't like this solution, you should
generate the QR-code locally, for example with [endroid/qr-code-bundle](https://github.com/endroid/qr-code-bundle).

In the configuration below, the Google QR Code generator service is replaced by our custom service. The data placeholder
indicates that `[DATA]` in the Uri will be replaced by the data to be set in the QR Code.

```yaml
scheb_two_factor:
    totp:
        qr_code_generator: 'https://my-qr-code-generator/[DATA]'
        qr_code_data_placeholder: '[DATA]'
```

If a user entity has a provisioning Uri stored, you can generate a nice-looking QR code from it, which can be scanned by the
mobile application.

```php
$totp = $container->get("scheb_two_factor.security.totp_authenticator")->getTotpForUser($user);
$url = $container->get("scheb_two_factor.security.totp_authenticator")->getUrl(TOTP $totp);
echo '<img src="'.$url.'" />';
```

You can then encode Provisioning Uri in a QR code the way you like (e.g. by using one of the many js-libraries).
 
```php
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;

public function index(TotpAuthenticatorInterface $twoFactor)
{
    // ...
    $totp = $twoFactor->getTotpForUser($user);
    $qrContent = $totp->getProvisioningUri()
}
```
