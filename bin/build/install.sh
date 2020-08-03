#!/bin/bash
set -e

if [ "$TEST_SUITE" = "integration" ]
then
    COMPOSER_WORKING_DIR="--working-dir=app"
fi

if [ "$SYMFONY_VERSION" != "" ]
then
    composer require "symfony/symfony:${SYMFONY_VERSION}" --no-update $COMPOSER_WORKING_DIR
fi

COMPOSER_MEMORY_LIMIT=-1 composer update --prefer-dist --no-interaction --no-suggest $COMPOSER_FLAGS $COMPOSER_WORKING_DIR
