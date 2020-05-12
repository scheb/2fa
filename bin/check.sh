#!/bin/bash
php-cs-fixer fix
./vendor/bin/phpcs --standard=php_cs.xml ./src ./tests ./app/src
./vendor/bin/psalm --show-info=true
./vendor/bin/phpunit
