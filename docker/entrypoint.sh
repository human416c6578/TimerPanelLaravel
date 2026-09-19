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
# confusing, so only do it outside of `local`. None of this is worth refusing
# to boot over — a cache store that happens to live in an unreachable database
# should surface as a page error, not as a crash loop.
warn_only() {
    "$@" || echo "entrypoint: '$*' failed, continuing" >&2
}

if [ "${APP_ENV:-production}" = "local" ]; then
    warn_only php artisan optimize:clear --no-ansi
else
    warn_only php artisan config:cache --no-ansi
    warn_only php artisan route:cache --no-ansi
    warn_only php artisan view:cache --no-ansi
fi

exec "$@"
