#!/bin/bash
set -e

if [ "$TEST_SUITE" = "unit" ]
then
    ./vendor/bin/phpunit
fi

if [ "$TEST_SUITE" = "integration" ]
then
    ./app/vendor/bin/phpunit -c ./app
fi
