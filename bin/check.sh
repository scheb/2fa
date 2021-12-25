#!/bin/bash
php-cs-fixer fix
./vendor/bin/phpcs
./vendor/bin/psalm
./vendor/bin/phpunit
