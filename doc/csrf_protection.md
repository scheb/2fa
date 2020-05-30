CSRF Protection
===============

To prevent CSRF attacks on the two-factor authentication form, you can enable CSRF protection the same way you would do
it on the login form.

First, make sure that the CSRF protection is enabled in the main configuration file:

```yaml
# app/config/config.yml
framework:
    csrf_protection: ~
```

Then, the `two_factor` security configuration needs a CSRF token provider. You can set this to use the default
provider available in the security component:

```yaml
# app/config/security.yml
security:
    firewalls:
        secured_area:
            two_factor:
                csrf_token_generator: security.csrf.token_manager
```

Make sure you add the extra field for the CSRF token in the authentication form. The code from the default template will
do the job:

```html
{% if isCsrfProtectionEnabled %}
    <input type="hidden" name="{{ csrfParameterName }}" value="{{ csrf_token(csrfTokenId) }}">
{% endif %}
```

You can change the name of the field by setting `csrf_parameter` and change the token ID by setting `csrf_token_id` in
your configuration:

```yaml
# app/config/security.yml
security:
    firewalls:
        secured_area:
            two_factor:
                csrf_parameter: _csrf_security_token
                csrf_token_id: a_private_string
```
