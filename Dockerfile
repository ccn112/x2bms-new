# syntax=docker/dockerfile:1
# X2-BMS production image — FrankenPHP + Laravel Octane. Dùng cho Coolify.
# Web, Horizon worker và scheduler dùng CHUNG image này (khác nhau ở command).

# --- Stage 1: build front-end assets (Vite) -------------------------------
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY resources ./resources
COPY vite.config.js ./
# public/build đã được commit; build lại để chắc chắn khớp source hiện tại.
COPY . .
RUN npm run build

# --- Stage 2: application -------------------------------------------------
FROM dunglas/frankenphp:1-php8.4 AS app

# Extensions cần cho Laravel + Filament + Medialibrary + Redis + Horizon.
RUN install-php-extensions pdo_mysql redis intl bcmath gd exif pcntl zip opcache

WORKDIR /app

# Composer (production, no dev).
COPY composer.json composer.lock ./
RUN --mount=from=composer:2,source=/usr/bin/composer,target=/usr/bin/composer \
    composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY . .
COPY --from=assets /app/public/build ./public/build

RUN --mount=from=composer:2,source=/usr/bin/composer,target=/usr/bin/composer \
    composer dump-autoload --optimize --no-dev \
 && mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENV OCTANE_SERVER=frankenphp \
    APP_ENV=production \
    APP_DEBUG=false

EXPOSE 8000

ENTRYPOINT ["entrypoint.sh"]
# Mặc định = web (Octane). Worker/scheduler override command trong compose.
CMD ["php", "artisan", "octane:frankenphp", "--host=0.0.0.0", "--port=8000", "--workers=auto", "--max-requests=512"]
