FROM phpdockerio/php:8.5-fpm
WORKDIR /application

ARG GIT_COMMIT_ID=""

# Install selected extensions and other stuff
RUN apt-get update \
    && apt-get -y --no-install-recommends install \
        php8.5-sqlite \
        php8.5-intl \
        php8.5-gd \
    && apt-get clean; rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /var/log/* /var/cache/* /usr/share/doc/*

COPY app/bin/console      ./bin/
COPY app/composer.*       ./
COPY app/data             ./data
COPY app/.env             ./.env
COPY app/config           ./config
COPY app/public           ./public/
COPY app/src              ./src
COPY app/templates        ./templates
COPY src                  ./../src

RUN composer install --no-dev --no-scripts \
    && composer clear-cache \
    && composer dump-autoload --optimize --classmap-authoritative \
    && bin/console cache:warmup \
    && chown -R www-data:www-data ./var/ \
    && chown -R www-data:www-data ./data \
    && echo "VERSION_COMMIT_ID=${GIT_COMMIT_ID}" >> .env \
    && date +"VERSION_DATE=\"%Y-%m-%d %H:%M:%S\"" >> .env
