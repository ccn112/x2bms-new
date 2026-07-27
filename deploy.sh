#!/usr/bin/env bash
# =============================================================================
# X2-BMS — Deploy script (chạy trên server backend sau khi đã `git pull`)
#
#   Mặc định (AN TOÀN, không mất dữ liệu):
#     ./deploy.sh
#       → composer install (prod) · npm build (Vite) · migrate --force
#         · storage:link · optimize + filament:optimize · queue restart
#
#   Kèm seed:
#     ./deploy.sh --seed     # migrate --force RỒI db:seed (thêm dữ liệu demo)
#     ./deploy.sh --fresh    # XOÁ SẠCH DB rồi migrate:fresh --seed  (có xác nhận)
#
#   Cờ khác:
#     --pull            # tự `git fetch && git reset --hard origin/<branch>` trước
#     -b, --branch NAME # nhánh cho --pull (mặc định: main)
#     --skip-assets     # bỏ qua npm ci && npm run build
#     -y, --yes         # không hỏi xác nhận (dùng với --fresh trong CI)
#     -h, --help
#
#   ⚠️ DemoDataSeeder KHÔNG idempotent (dùng create()) → chỉ seed trên DB TRỐNG.
#      Trên DB đã có dữ liệu, dùng --fresh (reset) chứ đừng --seed (sẽ trùng/lỗi).
#
#   Ghi đè binary nếu cần: PHP_BIN=php8.4 COMPOSER_BIN=composer NPM_BIN=npm ./deploy.sh
# =============================================================================
set -euo pipefail

# --- cờ mặc định -------------------------------------------------------------
DO_PULL=false
DO_SEED=false
DO_FRESH=false
SKIP_ASSETS=false
ASSUME_YES=false
BRANCH="main"

PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"
NPM_BIN="${NPM_BIN:-npm}"

# --- helper ------------------------------------------------------------------
log()  { printf '\n\033[1;36m▶ %s\033[0m\n' "$*"; }
warn() { printf '\033[1;33m! %s\033[0m\n' "$*"; }
die()  { printf '\033[1;31m✗ %s\033[0m\n' "$*" >&2; exit 1; }

usage() { sed -n '2,30p' "$0" | sed 's/^# \{0,1\}//'; }

# --- parse tham số -----------------------------------------------------------
while [[ $# -gt 0 ]]; do
  case "$1" in
    --pull)         DO_PULL=true ;;
    --seed)         DO_SEED=true ;;
    --fresh)        DO_FRESH=true ;;
    --skip-assets)  SKIP_ASSETS=true ;;
    -y|--yes)       ASSUME_YES=true ;;
    -b|--branch)    BRANCH="${2:?--branch cần tên nhánh}"; shift ;;
    -h|--help)      usage; exit 0 ;;
    *) die "Tham số không hợp lệ: $1  (xem ./deploy.sh --help)" ;;
  esac
  shift
done

# Luôn chạy tại thư mục gốc dự án (nơi đặt script), bất kể cwd.
cd "$(dirname "$0")"

# --- preflight ---------------------------------------------------------------
command -v "$PHP_BIN" >/dev/null 2>&1 || die "Không thấy PHP ('$PHP_BIN'). Đặt PHP_BIN=... nếu tên khác."
[[ -f artisan ]] || die "Không thấy 'artisan' — hãy chạy script trong thư mục gốc x2bms."
[[ -f .env ]]    || die "Chưa có file .env. Tạo .env (từ .env.example) + APP_KEY trước khi deploy."

# Pipeline ảnh (ImageVariantService) cần GD kèm WebP, và exif để xoay ảnh chụp
# dọc. THIẾU thì upload vẫn chạy nhưng KHÔNG sinh thumb/feed → app tải ảnh gốc
# vài MB cho mỗi ô lưới. Cảnh báo chứ không chặn deploy.
if ! "$PHP_BIN" -r 'exit(function_exists("gd_info") && (gd_info()["WebP Support"] ?? false) ? 0 : 1);' 2>/dev/null; then
  warn "PHP thiếu GD hoặc GD không hỗ trợ WebP → ảnh upload sẽ KHÔNG có bản thu nhỏ."
  warn "Cài: apt install php8.4-gd  (rồi restart php-fpm)."
fi
if ! "$PHP_BIN" -r 'exit(function_exists("exif_read_data") ? 0 : 1);' 2>/dev/null; then
  warn "PHP thiếu ext-exif → ảnh chụp DỌC bằng điện thoại sẽ hiển thị NẰM NGANG."
  warn "Cài: apt install php8.4-exif  (rồi restart php-fpm)."
fi

# --- maintenance mode: bật ngay, chỉ gỡ khi THÀNH CÔNG -----------------------
# Nếu lỗi giữa chừng → giữ maintenance để không phục vụ app hỏng.
DOWN=false
on_exit() {
  local code=$?
  if [[ $code -ne 0 && "$DOWN" == true ]]; then
    warn "Deploy LỖI (exit $code). App đang ở maintenance."
    warn "Sửa xong, gỡ bằng:  $PHP_BIN artisan up"
  fi
}
trap on_exit EXIT

if [[ -f vendor/autoload.php ]]; then
  "$PHP_BIN" artisan down --retry=15 >/dev/null 2>&1 && DOWN=true || true
fi

# --- (tuỳ chọn) cập nhật mã nguồn -------------------------------------------
if $DO_PULL; then
  log "Cập nhật mã nguồn: git reset --hard origin/$BRANCH"
  git fetch --all --prune
  git reset --hard "origin/$BRANCH"
fi

# --- composer (production) ---------------------------------------------------
log "composer install (--no-dev, tối ưu autoload)"
command -v "$COMPOSER_BIN" >/dev/null 2>&1 || die "Không thấy composer ('$COMPOSER_BIN')."
"$COMPOSER_BIN" install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# APP_KEY: sinh nếu chưa có (không ghi đè key đang dùng).
if ! grep -qE '^APP_KEY=base64:' .env; then
  warn "APP_KEY chưa set → php artisan key:generate"
  "$PHP_BIN" artisan key:generate --force
fi

# --- build asset Vite --------------------------------------------------------
if $SKIP_ASSETS; then
  warn "Bỏ qua build asset (--skip-assets)."
else
  log "Build Vite: npm ci && npm run build"
  command -v "$NPM_BIN" >/dev/null 2>&1 || die "Không thấy npm ('$NPM_BIN'). Cài Node 20 LTS hoặc dùng --skip-assets."
  if [[ -f package-lock.json ]]; then "$NPM_BIN" ci; else "$NPM_BIN" install; fi
  "$NPM_BIN" run build
  [[ -f public/build/manifest.json ]] || die "Build xong nhưng thiếu public/build/manifest.json — kiểm tra Vite."
fi

# --- migrate / seed ----------------------------------------------------------
if $DO_FRESH; then
  warn "CHẾ ĐỘ --fresh: sẽ XOÁ TOÀN BỘ bảng rồi migrate:fresh --seed."
  if ! $ASSUME_YES; then
    read -r -p "Gõ đúng chữ 'FRESH' để xác nhận xoá sạch DB: " ans
    [[ "$ans" == "FRESH" ]] || die "Đã huỷ (không xác nhận)."
  fi
  log "migrate:fresh --seed --force"
  "$PHP_BIN" artisan migrate:fresh --seed --force
else
  log "migrate --force"
  "$PHP_BIN" artisan migrate --force
  if $DO_SEED; then
    warn "db:seed (DemoDataSeeder — KHÔNG idempotent; chỉ nên chạy trên DB trống)."
    "$PHP_BIN" artisan db:seed --force
  fi
fi

# --- storage link (bắt buộc để serve /storage: avatar, ảnh public) ----------
# Dùng --force để luôn đảm bảo symlink đúng (kể cả khi link cũ thiếu/gãy sau khi
# clone mới) — thiếu link này thì ảnh upload trả 404.
log "storage:link --force"
"$PHP_BIN" artisan storage:link --force

# --- cache production --------------------------------------------------------
log "Tối ưu cache (config/route/view/event + filament)"
"$PHP_BIN" artisan optimize
"$PHP_BIN" artisan filament:optimize

# --- nạp lại worker ----------------------------------------------------------
log "Nạp lại queue worker"
"$PHP_BIN" artisan queue:restart || true
# Nếu dùng Horizon (redis) — phát tín hiệu reload; bỏ qua nếu không chạy.
"$PHP_BIN" artisan horizon:terminate >/dev/null 2>&1 || true

# --- gỡ maintenance (chỉ khi tới được đây = thành công) ----------------------
if [[ "$DOWN" == true ]]; then
  "$PHP_BIN" artisan up
  DOWN=false
fi

log "✅ Deploy hoàn tất."
"$PHP_BIN" artisan about --only=environment 2>/dev/null || true
