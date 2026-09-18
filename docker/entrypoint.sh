#!/bin/sh
set -e

cd /app

# Runtime-writable directories. Named volumes mounted over storage/ or
# database/ start empty, so recreate the tree on every boot.
mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

# The application's own database is sqlite; the game database is external and
# is never created or migrated from here.
if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    DB_FILE="${DB_DATABASE:-/app/database/database.sqlite}"
    [ -f "$DB_FILE" ] || touch "$DB_FILE"
fi

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force --graceful --no-ansi
fi

# Caching config/routes/views is a production win but makes local editing
# confusing, so only do it outside of `local`.
if [ "${APP_ENV:-production}" = "local" ]; then
    php artisan optimize:clear --no-ansi
else
    php artisan config:cache --no-ansi
    php artisan route:cache --no-ansi
    php artisan view:cache --no-ansi
fi

exec "$@"
