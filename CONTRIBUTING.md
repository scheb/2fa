Contributing
============
👍🎉 First off, thanks for taking the time to contribute! 🎉👍

Submitting a Bug Report
-----------------------

Before you report a bug, please check the [troubleshooting guide](doc/troubleshooting.md) first.

When creating the bug report, please follow the bug template and provide the details requested.

Creating a Pull Request
-----------------------

You're welcome to [contribute](https://github.com/scheb/2fa/graphs/contributors) new features by creating a pull
requests or feature request in the issues section. Besides new features,
[translations](src/bundle/Resources/translations) are highly welcome.

For pull requests, please follow these guidelines:

- Do not use any PHP language features above the minimum supported version (see `composer.json`)
- Symfony code style (use `php_cs.xml` to configure PHP Code Sniffer checks in your IDE)
- PHP 7.1 type hints for everything (including: return types, `void`, nullable types)
- `declare(strict_types=1)` must be used
- Methods/variables/constants must declare visibility
- Please add/update test cases
- Test methods should be named `[method]_[scenario]_[expectedResult]`

Running Quality Checks
----------------------

Before you create a pull request, please make sure your changes fulfill the quality criteria. CI is also checking these,
but running them locally gives you the chance to catch any errors before you push changes.

First, install the dependencies by running `composer install` in the project root.

### Code Style

- Run PHP CodeSniffer with `vendor/bin/phpcs --standard=php_cs.xml app/src src tests`
- Run Psalm with `vendor/bin/psalm` and address any error-level issues
- Run [PHP-CS-Fixer](https://github.com/FriendsOfPHP/PHP-CS-Fixer) (not provided with the library) with `php-cs-fixer fix`

### Unit Tests

- Run the unit tests with `vendor/bin/phpunit`

### Integration Tests

The library comes with its own little test application to play around with 2fa and to run integration tests. For more
information, have a look at the [Readme file in the `app` folder](app/README.md). In short, to run the integration
tests:

1) Change the working directory to the `app` folder

**In the `app` folder:**

2) Install the test application dependencies with `composer install`
3) Run the integration tests with `vendor/bin/phpunit`
