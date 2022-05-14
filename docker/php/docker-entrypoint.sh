#!/bin/sh

php vendor/bin/doctrine orm:schema:update --force

exec docker-php-entrypoint "$@"
