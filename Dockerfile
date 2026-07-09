# Pulse (Laravel) container image.
# Render has no native PHP runtime, so — unlike pulse-api (Python) and
# pulse-rails (Ruby) — this port ships a Dockerfile. See DEPLOY.md.
FROM php:8.4-cli

# PHP extensions Laravel needs beyond the CLI defaults. This community installer
# pulls in the right system libraries automatically.
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions \
    && install-php-extensions pdo_sqlite mbstring bcmath

# Composer, copied from its official image.
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /app
COPY . .

# Production dependencies only, with an optimized autoloader. Then create the
# SQLite file and make the writable dirs writable.
RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && touch database/database.sqlite \
    && chmod -R ug+rw database storage bootstrap/cache

ENV APP_ENV=production \
    APP_DEBUG=false \
    DB_CONNECTION=sqlite

# SQLite on the free tier is ephemeral, so migrate + seed on each boot (both are
# idempotent). Render provides $PORT; the built-in server is enough for a demo.
CMD php artisan migrate --force \
    && php artisan db:seed --force \
    && php artisan config:cache \
    && php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
