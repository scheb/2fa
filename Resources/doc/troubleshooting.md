Troubleshooting
===============

How to debug and solve common issue related to the bundle.


## Logout redirects back to the two-factor authentication form

### Problem

When the two-factor authentication form is shown, you want to cancel the two-factor authentication process. You click
the "cancel" link, which should execute a logout. It does not execute the logout, but redirects back to the two-factor
authentication form.

### Solution

If you see such behavior, the `access_control` rules from the security configuration don't allow accessing the logout
path. Your logout path must be accessible to user with any authentication state, which is usually done by allowing it
for `IS_AUTHENTICATED_ANONYMOUSLY`. You're most likely missing a rule under `access_control` in the security
configuration.

The configuration should look similar to this:

```
# config/packages/security.yaml
security:
    access_control:
        - { path: ^/logout, role: IS_AUTHENTICATED_ANONYMOUSLY }
        # More rules here ...
```

Make sure the rule comes first in the list, since access control rules are evaluated in order.

If you have such a rule and it still doesn't work, for some reason the rule is not matching. Make absolutely sure the
`path` regular expression matches your logout path. If you have additional options, such as `host` or `ip`, check that
they're matching too.


## Not logged in after completing two-factor authentication

### Problem

After you logged in and have successfully passed the two-factor authentication process, you're not logged in. Either you
are redirected back to the login page or the page is shown with the authenticated user missing.

### Troubleshooting

1) Disable two-factor authentication by commenting out all `two_factor` settings in the security firewall configuration.
   Try to login. Does it work?

   - Yes, it works -> Continue with 2)
   - No, it does not work -> Your login process is broken.
       - **Solution:** Can't exactly tell what's wrong. Continue debugging the login issue. Solve this issue first,
         before you re-enable two-factor authentication.

2) Revert the changes from 1). Debug the security token on the two-factor authentication form page by `var_dump`-ing it
   or any other suitable method.

   The token should be of type `TwoFactorToken` and the field `authenticatedToken` should contain an authenticated
   security token. Does that authenticated token have `authenticated`=`false` set?

   - Yes -> Your authenticated token was flagged as invalid. Follow solution below.
   - No -> Continue with 3)

3) After completing two-factor authentication, when you end up in the unauthenticated state, check the last request few
   requests in the Symfony profiler.

   For each of the requests, go to Logs -> Debug.

   Does it say `Cannot refresh token because user has changed` or `Token was deauthenticated after trying to refresh
   it`?

   - Yes -> Your authenticated token was flagged as invalid. Follow solution below.
   - No -> Unknown issue. Try to reach out for help by
     [creating an issue](https://github.com/scheb/two-factor-bundle/issues/new) and let us know what you've already
     tested.

**Solution to: Your authenticated token was flagged as invalid**

Most likely your user entity implements the `\Serializable` interface and not all of the field relevant to the
authentication process are taken by serialize/unserialize. Check which fields are used in methods `serialize()` and
`deserialize()`.

It must be at least the fields that are used in the methods from `Symfony\Component\Security\Core\User\UserInterface`.

If your user entity implements `Symfony\Component\Security\Core\User\AdvancedUserInterface`, you also need the fields
that are used in `isAccountNonExpired()`, `isAccountNonLocked()`, `isCredentialsNonExpired()` and `isEnabled()`.


## Two-factor authentication form is not shown after login

### Problem

After successful login, the two-factor authentication form is not shown. Instead you're either logged in or you see
a different page from your application.

### Troubleshooting

Basic checks:
- Your login page belongs to the firewall, which has two-factor authentication configured.
- The paths of login page, login check, 2fa and 2fa check are all located with the firewall's path pattern.
- Your user entity has the interfaces implemented, which are necessary for the two-factor authentication method.
- Your user entity fulfills the requirements of at least one two-factor authentication method.

1) Is a `TwoFactorToken` present after the login?

   - Yes -> Continue with 2)
   - No -> Continue with 3)

2) Try accessing a page that requires the user to be authenticated. Does it redirect to the two-factor authentication
   form?

   - Yes:
       - **Solution:** The page you've seen after login doesn't require a fully authenticated user. Most likely that
         path is accessible to `IS_AUTHENTICATED_ANONYMOUSLY` via your security `access_control` configuration. Either
         change your `access_control` configuration or after login force-redirect to user to a page that requires full
         authentication.
   - No -> Unknown issue. Try to reach out for help by
     [creating an issue](https://github.com/scheb/two-factor-bundle/issues/new) and let us know what you've already
     tested.

3) On login, do you reach the end (return statement) of method
   `Scheb\TwoFactorBundle\Security\Authentication\Provider\AuthenticationProviderDecorator::authenticate()`?

   - Yes -> Continue with 4)
   - No -> Something is wrong with the integration of the bundle. Try to reach out for help by
     [creating an issue](https://github.com/scheb/two-factor-bundle/issues/new) and let us know what you've already
     tested.

4) On login, is method
   `Scheb\TwoFactorBundle\Security\TwoFactor\Handler\TwoFactorProviderHandler::getActiveTwoFactorProviders()` called?

   - Yes, it's called -> Continue with 5)
   - No it's not called:
      - **Solution:** Two-factor authentication is skipped, either because of the IP whitelist or because of a trusted
        device token. IP whitelist is part of the bundle's configuration. Maybe you have whitelisted "localhost" or
        "127.0.0.1"? The trusted device cookie can be removed with your browser's developer tools.

5) Does `Scheb\TwoFactorBundle\Security\TwoFactor\Handler\TwoFactorProviderHandler::getActiveTwoFactorProviders()`
   return any values?

   - Yes, it returns an array of strings -> Unknown issue. Try to reach out for help by
     [creating an issue](https://github.com/scheb/two-factor-bundle/issues/new) and let us know what you've already
     tested.
   - No, it returns an empty array:
       - **Solution:** our user doesn't have an active two-factor authentication method. Either the `is*Enabled` method
         returns `false` or an essential piece of data (e.g. Google Authenticator secret) is missing.
