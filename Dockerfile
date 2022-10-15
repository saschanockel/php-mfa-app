ARG PHP_VERSION=8.1.11
ARG COMPOSER_VERSION=2
ARG IMAGICK_EXT_VERSION=3.7.0
ARG REDIS_EXT_VERSION=5.3.7
ARG XDEBUG_EXT_VERSION=3.1.5

FROM composer:$COMPOSER_VERSION AS builder

RUN mkdir /build

WORKDIR /build

COPY ./composer.json ./composer.json
COPY ./composer.lock ./composer.lock

RUN composer install --ignore-platform-reqs

FROM php:${PHP_VERSION}-fpm-alpine AS production
ARG IMAGICK_EXT_VERSION
ARG REDIS_EXT_VERSION

# Update image packages
RUN apk update && apk upgrade

# Install dependencies, only dependencies installed with pecl need to be enabled manually
RUN apk add --no-cache $PHPIZE_DEPS imagemagick imagemagick-dev \
    && pecl install imagick-$IMAGICK_EXT_VERSION redis-$REDIS_EXT_VERSION \
    && docker-php-ext-enable imagick redis \
    && docker-php-ext-install mysqli

# Use the production configuration
COPY ./docker/php/production/php.ini /usr/local/etc/php/php.ini

# Copy files needed by the app
COPY ./public/ /var/www/html/public/
COPY ./src/ /var/www/html/src/
COPY ./templates/ /var/www/html/templates/
COPY ./docker/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
COPY --from=builder /build/vendor /var/www/html/vendor/

# Make the app run as an unprivileged user for additional security
USER www-data

ENTRYPOINT ["docker-entrypoint"]
CMD ["php-fpm"]

FROM php:${PHP_VERSION}-fpm-alpine AS development
ARG IMAGICK_EXT_VERSION
ARG REDIS_EXT_VERSION
ARG XDEBUG_EXT_VERSION

# Update image packages
RUN apk update && apk upgrade

# Install dependencies and development tools, only dependencies installed with pecl need to be enabled manually
RUN apk add --no-cache $PHPIZE_DEPS imagemagick imagemagick-dev \
    && pecl install imagick-$IMAGICK_EXT_VERSION redis-$REDIS_EXT_VERSION xdebug-$XDEBUG_EXT_VERSION \
    && docker-php-ext-enable imagick redis xdebug \
    && docker-php-ext-install mysqli

COPY ./docker/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint

# Make the app run as the most common uid:gid to avoid permission mismatches when executing commands inside the container
USER 1000:1000

ENTRYPOINT ["docker-entrypoint"]
CMD ["php-fpm"]
