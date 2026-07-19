# syntax=docker/dockerfile:1
# X2-BMS production image — FrankenPHP + Laravel Octane. Dùng cho Coolify.
# Web, Horizon worker và scheduler dùng CHUNG image này (khác nhau ở command).
#
# Tối ưu tốc độ build (Coolify/BuildKit):
#  - Stage `vendor` cài composer 1 lần, dùng lại cho cả assets (Tailwind cần vendor)
#    và app (kế thừa vendor) → không cài extension/composer lặp lại.
#  - Layer deps chỉ phụ thuộc lock file → đổi code không cài lại.
#  - Cache mount cho ~/.npm & composer cache → build sau không tải lại package.
#  - PLAYWRIGHT_SKIP_BROWSER_DOWNLOAD: bỏ tải browser Playwright (chỉ cần cho test).

# --- Stage 1: vendor (Composer deps) --------------------------------------
# frankenphp base có sẵn PHP + extension nên thoả platform requirements của composer.
FROM dunglas/frankenphp:1-php8.4 AS vendor
RUN install-php-extensions pdo_mysql redis intl bcmath gd exif pcntl zip opcache
WORKDIR /app
ENV COMPOSER_CACHE_DIR=/tmp/composer-cache
COPY composer.json composer.lock ./
RUN --mount=from=composer:2,source=/usr/bin/composer,target=/usr/bin/composer \
    --mount=type=cache,target=/tmp/composer-cache \
    composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

# --- Stage 2: build front-end assets (Vite + Tailwind v4) ------------------
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
ENV PLAYWRIGHT_SKIP_BROWSER_DOWNLOAD=1
RUN --mount=type=cache,target=/root/.npm \
    npm ci --no-audit --no-fund
# Tailwind v4 quét source + @import từ vendor (theme Filament) → cần cả hai.
COPY . .
COPY --from=vendor /app/vendor ./vendor
RUN npm run build

# --- Stage 3: application (kế thừa vendor: đã có extension + vendor) --------
FROM vendor AS app
WORKDIR /app

COPY . .
COPY --from=assets /app/public/build ./public/build

RUN --mount=from=composer:2,source=/usr/bin/composer,target=/usr/bin/composer \
    --mount=type=cache,target=/tmp/composer-cache \
    composer dump-autoload --optimize --no-dev \
 && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
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
