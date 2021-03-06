Custom Conditions for Two-Factor Authentication
===============================================

In your application, you may have extra requirements when to perform two-factor authentication, which goes beyond what
the bundle is doing automatically. In such a case you need to implement
`\Scheb\TwoFactorBundle\Security\TwoFactor\Condition\TwoFactorConditionInterface`:

```php
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
```

Register it as a service and configure the service name:

```yaml
# config/packages/scheb_two_factor.yaml
scheb_two_factor:
    two_factor_condition: acme.custom_two_factor_condition
```
