Two-Factor Authentication in an API
===================================

This guide describes how to set-up two-factor authentication in a Symfony application that doesn't generate a frontend,
but provides API endpoints instead.

## Prerequisites

To make two-factor authentication work in an API, your **API has to be stateful**. That means the API is starting a
session which is passed by the client on every call. The session is necessary for two-factor authentication to store the
state of the login - if the user has already completed two-factor authentication or not.

If you use a custom authenticator (you may have followed Symfony's guide
[Custom Authentication System with Guard (API Token Example)](https://symfony.com/doc/current/security/guard_authentication.html)),
please make sure your authenticator doesn't authenticate on every request, but only when the
authentication route is called. For an example, have a look at the
[Avoid Authenticating the Browser on Every Request](https://symfony.com/doc/current/security/guard_authentication.html#avoid-authenticating-the-browser-on-every-request)
section in the Symfony guide.

## Setup

ℹ️ For simplicity of this guide, it is assumed that you're building a JSON API and you're using the `json_login`
authentication mechanism, which comes with Symfony. For any other authentication mechanism it should work the same or at
least similar, as long as it lets you configure a custom success handler.

You need to implement 4 classes:

1) A custom success handler for the authentication mechanism
2) A custom "two-factor authentication required" handler for the two-factor authentication
3) A custom success handler for the two-factor authentication
4) A custom failure handler for the two-factor authentication

### Configuration

Please make sure the following configuration options are set on your firewall:

```yaml
# config/packages/security.yaml
security:
    firewalls:
        your_firewall_name:
            # ...
            two_factor:
                prepare_on_login: true
                prepare_on_access_denied: true
```

### 1) Response on login

This first response is returned after the user logged in. Without two-factor authentication, it would either return
a "login success" or "login failure" response. With two-factor authentication, you eventually need to return a third
type of response to tell the client that authentication hasn't completed yet and two-factor authentication is required.
The client should show the two-factor authentication form then.

If you provide multiple authentication mechanisms for the user to identify themselves, you have to do this for each one
of them.

To implement such a response you need to a custom success handler:

```php
<?php

namespace App\Security;

use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class AuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        if ($token instanceof TwoFactorTokenInterface) {
            // Return the response to tell the client two-factor authentication is required.
            return new Response('{"login": "success": "2fa_complete": false}');
        }

        // Otherwise return the default response for successful login. You could do this by decorating
        // the original authentication success handler and calling it here.
   }
}
```

Register it as a service and configure it as a custom `success_handler` for the authentication method:

```yaml
# app/config/security.yml
security:
    firewalls:
        your_firewall_name:
            json_login:  # The authentication mechanism you're using
                success_handler: your_api_success_handler
```

### 2) Response to require two-factor authentication

You need a response that is returned when the user requests a path, but it is not accessible (yet), because the user
has to complete two-factor authentication first. This could be the same as your "access denied" response.

Create a class which implements `Scheb\TwoFactorBundle\Security\Http\Authentication\AuthenticationRequiredHandlerInterface`
to return the response.

```php
<?php

namespace App\Security;

use Scheb\TwoFactorBundle\Security\Http\Authentication\AuthenticationRequiredHandlerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class TwoFactorAuthenticationRequiredHandler implements AuthenticationRequiredHandlerInterface
{
    public function onAuthenticationRequired(Request $request, TokenInterface $token): Response
    {
        // Return the response to tell the client that authentication hasn't completed yet and
        // two-factor authentication is required.
        return new Response('{"error": "access_denied", "2fa_complete": false}');
    }
}
```

Register it as a service and configure it as the `required_handler` of the `two_factor` authentication method:

```yaml
# app/config/security.yml
security:
    firewalls:
        your_firewall_name:
            two_factor:
                authentication_required_handler: your_api_2fa_required_handler
```

### 3) Response when two-factor authentication was successful

You need a response that is returned when two-factor authentication was completed successfully and the user is now
fully authenticated. Implement another success handler for it:

```php
<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class TwoFactorAuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        // Return the response to tell the client that authentication including two-factor
        // authentication is complete now.
        return new Response('{"login": "success", "2fa_complete": true}');
   }
}
```

Register it as a service and configure it as the `success_handler` of the `two_factor` authentication method:

```yaml
# app/config/security.yml
security:
    firewalls:
        your_firewall_name:
            two_factor:
                success_handler: your_api_2fa_success_handler
```

### 4) Response when two-factor authentication failed

You need a response that is returned when two-factor authentication was tried, but authentication failed for some
reason. Implement a failure handler for it:

```php
<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

class TwoFactorAuthenticationFailureHandler implements AuthenticationFailureHandlerInterface
{
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        // Return the response to tell the client that 2fa failed. You may want to add more details
        // from the $exception.
        return new Response('{"error": "2fa_failed", "2fa_complete": false}');
   }
}
```

Register it as a service and configure it as the `failure_handler` of the `two_factor` authentication method:

```yaml
# app/config/security.yml
security:
    firewalls:
        your_firewall_name:
            two_factor:
                failure_handler: your_api_2fa_failure_handler
```
