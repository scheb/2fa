Contributing
============
👍🎉 First off, thanks for taking the time to contribute! 🎉👍

Submitting a Bug Report
-----------------------

Before you report a bug, please check the [troubleshooting guide](doc/troubleshooting.md) first.

When creating the bug report, please follow the bug template and provide details about the Symfony and bundle version
you're using.

Creating a Pull Request
-----------------------

You're welcome to [contribute](https://github.com/scheb/2fa/graphs/contributors) new features by creating
a pull requests or feature request in the issues section. Besides new features,
[translations](src/bundle/Resources/translations) are highly welcome.

For pull requests, please follow these guidelines:

- Symfony code style (use `php_cs.xml` to configure the code style in your IDE)
- PHP7.1 type hints for everything (including: return types, `void`, nullable types)
- `declare(strict_types=1)` must be used
- Please add/update test cases
- Test methods should be named `[method]_[scenario]_[expectedResult]`

### Running Quality Checks

Before you create a pull request, please make sure your changes fulfill the quality criteria:

1) Install the dependencies with `composer install` in the project root
2) Run the unit tests with `vendor/bin/phpunit`
3) Run PHP CodeSniffer with `vendor/bin/phpcs --standard=php_cs.xml src tests app/src`
4) Run Psalm with `vendor/bin/psalm` and address any error-level issues
5) Run [PHP-CS-Fixer](https://github.com/FriendsOfPHP/PHP-CS-Fixer) (not provided with the library)

To run the integration tests, change your working directory to the `app` folder. Within that folder:

1) Install the dependencies with `composer install`
2) Run the integration tests with `vendor/bin/phpunit`
