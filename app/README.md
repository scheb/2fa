scheb/2fa - app
===============

**Test application and integration tests for [scheb/2fa](https://github.com/scheb/2fa).**

Setup
-----

To setup the test application, run `composer install` in **this directory** (the `app` directory!).

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
