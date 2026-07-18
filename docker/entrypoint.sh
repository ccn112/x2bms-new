#!/bin/sh
set -e

# Chạy MỘT LẦN cho container web (WEB_ROLE=true). Worker/scheduler bỏ qua migrate/optimize
# để tránh chạy song song. Đặt WEB_ROLE=true ở service web, bỏ trống ở worker/scheduler.

# Đảm bảo thư mục storage tồn tại (volume rỗng lần đầu).
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

if [ "${WEB_ROLE:-false}" = "true" ]; then
    php artisan storage:link || true
    php artisan migrate --force
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
fi

exec "$@"
