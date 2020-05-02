#!/bin/bash
php-cs-fixer fix
./vendor/bin/phpcs --standard=php_cs.xml --ignore=*/vendor/* .
./vendor/bin/psalm --show-info=true
