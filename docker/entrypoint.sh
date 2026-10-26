#!/bin/sh
set -eu

cd /var/www/html

if [ ! -f .env ]; then
    cp .env.example .env
fi

mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
composer install --no-interaction --prefer-dist

# Discard host-specific cached paths and database settings.
php artisan config:clear
php artisan view:clear

if ! grep -Eq '^APP_KEY=.+$' .env; then
    php artisan key:generate --no-interaction
fi

if [ ! -f database/database.sqlite ]; then
    touch database/database.sqlite
fi

php artisan migrate --force --no-interaction
chown -R www-data:www-data storage bootstrap/cache database

exec docker-php-entrypoint "$@"
