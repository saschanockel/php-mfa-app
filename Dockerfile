FROM composer:2 AS builder

RUN mkdir /build

WORKDIR /build

COPY ./composer.json ./composer.json
COPY ./composer.lock ./composer.lock

RUN composer install

FROM php:8.1.9-fpm-alpine AS production

# Update image packages
RUN apk update && apk upgrade

# Install dependencies, only dependencies installed with pecl need to be enabled manually
RUN apk add --no-cache $PHPIZE_DEPS imagemagick imagemagick-dev \
    && pecl install redis-5.3.6 imagick-3.7.0 \
    && docker-php-ext-enable redis imagick \
    && docker-php-ext-install mysqli

# Use the production configuration
COPY ./docker/php/production/php.ini /usr/local/etc/php/php.ini

COPY ./public/ /var/www/html/public/
COPY ./src/ /var/www/html/src/
COPY ./templates/ /var/www/html/templates/
COPY --from=builder /build/vendor /var/www/html/vendor/
COPY ./docker/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint

# Make the app run as an unprivileged user for additional security
USER www-data

ENTRYPOINT ["docker-entrypoint"]
CMD ["php-fpm"]

FROM php:8.1.9-fpm-alpine AS development

# Update image packages
RUN apk update && apk upgrade

# Install dependencies and development tools, only dependencies installed with pecl need to be enabled manually
RUN apk add --no-cache $PHPIZE_DEPS imagemagick imagemagick-dev \
    && pecl install xdebug-3.1.2 redis-5.3.6 imagick-3.7.0 \
    && docker-php-ext-enable xdebug redis imagick \
    && docker-php-ext-install mysqli

COPY ./docker/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint

# Make the app run as an unprivileged user for additional security
USER www-data

ENTRYPOINT ["docker-entrypoint"]
CMD ["php-fpm"]
