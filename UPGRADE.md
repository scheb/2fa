Upgrading
=========

Here's an overview if you have to do any work when upgrading.

## 4.x to 5.x

Guard-based authentication has become the preferred way of building a custom authentication provider. Therefore,
`Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken` is now configured per default in `security_tokens`
as a token to triggers two-factor authentication. If you don't want to have it automatically configured, please set
`security_tokens` in your bundle configuration.

The `check_path` now accepts the two-factor authentication code only with a POST request. This can be changed by setting
the `post_only: false` option on the firewall.

```yaml
# config/packages/security.yaml
security:
    firewalls:
        yourFirewallName:
            # ...
            two_factor:
                post_only: false
```
