Trusted Devices
===============

You can give users the possibility to flag devices as "trusted", which means the two-factor process will be skipped after
passing it once on that device.

You have to enable this feature in your configuration:

```yaml
# config/packages/scheb_two_factor.yaml
scheb_two_factor:
    trusted_device:
        enabled: false                 # If the trusted device feature should be enabled
        lifetime: 5184000              # Lifetime of the trusted device token
        extend_lifetime: false         # Automatically extend lifetime of the trusted cookie on re-login
        cookie_name: trusted_device    # Name of the trusted device cookie
        cookie_secure: false           # Set the 'Secure' (HTTPS Only) flag on the trusted device cookie
        cookie_same_site: "lax"        # The same-site option of the cookie, can be "lax" or "strict"
        cookie_domain: ".example.com"  # Domain to use when setting the cookie, fallback to the request domain if not set
        cookie_path: "/"               # Path to use when setting the cookie
```

Trusted device cookies are versioned, which gives you (or the user) to possibility to invalidate all trusted device
cookies at once, e.g. in case of a security breach. To make use of this feature, you have to implement
`Scheb\TwoFactorBundle\Model\TrustedDeviceInterface` in the user entity.

```php
namespace Acme\DemoBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Scheb\TwoFactorBundle\Model\TrustedDeviceInterface;

class User implements TrustedDeviceInterface
{
    /**
     * @ORM\Column(type="integer")
     */
    private $trustedVersion;

    // [...]

    public function getTrustedTokenVersion(): int
    {
        return $this->trustedVersion;
    }
}
```

If not implemented, the bundle is defaulting to version `0`.

## Custom trusted device manager

If you don't like the way this is implemented, you can also have your own trusted device manager. Create a service
implementing `Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\TrustedDeviceManagerInterface` and register it in the
configuration:

```yaml
# config/packages/scheb_two_factor.yaml
scheb_two_factor:
    trusted_device:
        manager: acme.custom_trusted_device_manager  # Use a custom trusted device manager
```

## Conditions for trusted devices

There is a way to check if a device/user fulfills certain conditions, before a device is flagged as "trusted". For
example, you may want to allow trusted devices only within your internal network. In that case, please implement your
own instance of the trusted device manager (as described above) and implement the `canSetTrustedDevice` method with the
decision logic you need.

```php
public function canSetTrustedDevice($user, Request $request, string $firewallName): bool
{
    return true; // Always allow trusted device feature
}
```
