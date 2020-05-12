#!/bin/bash
set -e

if [ "$TEST_SUITE" = "UNIT" ]
then
    ./vendor/bin/phpunit
fi

if [ "$TEST_SUITE" = "INTEGRATION" ]
then
    ./app/vendor/bin/phpunit -c ./app
fi
