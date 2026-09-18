# syntax=docker/dockerfile:1
#
# Build context is the repository root; the Laravel application lives in laravel/.

# ---------------------------------------------------------------------------
# base: PHP runtime shared by every other stage
# ---------------------------------------------------------------------------
FROM dunglas/frankenphp:1-php8.4-alpine AS base

RUN install-php-extensions \
        pdo_mysql \
        opcache \
        intl \
        zip \
        pcntl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php/app.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/Caddyfile /etc/frankenphp/Caddyfile

WORKDIR /app

# ---------------------------------------------------------------------------
# vendor: production PHP dependencies
# ---------------------------------------------------------------------------
FROM base AS vendor

COPY laravel/composer.json laravel/composer.lock ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-interaction \
        --no-progress \
        --prefer-dist \
        --optimize-autoloader

# ---------------------------------------------------------------------------
# assets: compiled CSS/JS. Needs vendor/ because resources/css/app.css imports
# flux.css and scans vendor blade stubs through Tailwind's @source globs.
# ---------------------------------------------------------------------------
FROM node:22-alpine AS assets

WORKDIR /app

COPY laravel/package.json laravel/package-lock.json ./
RUN npm ci --no-audit --no-fund

COPY laravel/vite.config.js ./
COPY laravel/resources ./resources
COPY --from=vendor /app/vendor ./vendor
RUN npm run build

# ---------------------------------------------------------------------------
# test: full source + dev dependencies, used by CI to run Pint and Pest
# ---------------------------------------------------------------------------
FROM base AS test

ENV APP_ENV=testing \
    SERVER_NAME=:8080

COPY laravel/composer.json laravel/composer.lock ./
RUN composer install \
        --no-scripts \
        --no-interaction \
        --no-progress \
        --prefer-dist

COPY laravel/ ./
COPY --from=assets /app/public/build ./public/build
# A throwaway .env with a generated APP_KEY, the same way CI has always set the
# test environment up.
RUN composer dump-autoload --optimize \
    && cp .env.example .env \
    && php artisan key:generate --force --no-ansi \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache

CMD ["sh", "-c", "vendor/bin/pint --test && vendor/bin/pest"]

# ---------------------------------------------------------------------------
# dev: the test image wired up as a running server, for docker-compose.dev.yml
# ---------------------------------------------------------------------------
FROM test AS dev

ENV APP_ENV=local \
    APP_DEBUG=true \
    RUN_MIGRATIONS=true

COPY docker/php/dev.ini /usr/local/etc/php/conf.d/zzz-dev.ini
COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

EXPOSE 8080

ENTRYPOINT ["entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]

# ---------------------------------------------------------------------------
# production: the image that actually ships
# ---------------------------------------------------------------------------
FROM base AS production

ENV APP_ENV=production \
    APP_DEBUG=false \
    SERVER_NAME=:8080 \
    RUN_MIGRATIONS=true

COPY --chown=www-data:www-data laravel/ ./
COPY --from=vendor --chown=www-data:www-data /app/vendor ./vendor
COPY --from=assets --chown=www-data:www-data /app/public/build ./public/build
COPY docker/entrypoint.sh /usr/local/bin/entrypoint

RUN rm -rf tests phpunit.xml .editorconfig \
    && composer dump-autoload --optimize --no-dev --no-scripts \
    && php artisan package:discover --ansi \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && mkdir -p /data/caddy /config/caddy \
    && chown -R www-data:www-data /app /data/caddy /config/caddy \
    && chmod +x /usr/local/bin/entrypoint

USER www-data

EXPOSE 8080

HEALTHCHECK --interval=15s --timeout=5s --start-period=30s --retries=5 \
    CMD curl --fail --silent http://localhost:8080/up || exit 1

ENTRYPOINT ["entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]
