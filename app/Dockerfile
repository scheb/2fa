FROM php:8.5-fpm
WORKDIR /application

ARG GIT_COMMIT_ID=""

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install selected extensions and other stuff
RUN apt-get update \
    && apt-get -y --no-install-recommends install \
        git \
        libfreetype-dev \
        libicu-dev \
        libjpeg-dev \
        libpng-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        gd \
        intl \
        zip \
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
