scheb/2fa - app
===============

**Test application and integration tests for [scheb/2fa](https://github.com/scheb/2fa).**

Setup
-----

To setup the test application, run `composer install` in **this directory**.

Test Application
----------------

Start the application via [Symfony CLI](https://symfony.com/download) by running `symfony serve` in this directory.

There's a pre-configured user in the SQLite database that you can use to login and test 2fa:

- Username: `user1`
- Password: `test`
- Backup codes for 2fa: `111` and `222`

Integration tests
-----------------

To execute the integration tests, run `vendor/bin/phpunit` in this directory.

Configuration Presets
---------------------

This test application comes with multiple configuration presets:

- `default` - The default preset, using the classic Symfony security system
- `authenticators` - Using the newer "authenticator"-based Symfony security system

To run the test application with a specific preset, set the environment variable `TEST_CONFIG`. For example, to use the
`authenticators` preset:

```bash
# Start the application with the config preset
TEST_CONFIG=authenticators symfony serve

# Run integration tests with the config preset
TEST_CONFIG=authenticators vendor/bin/phpunit
```

**It's advised to delete the `var/cache` folder whenever you change configuration presets.**
