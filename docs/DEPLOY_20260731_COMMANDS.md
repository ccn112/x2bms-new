# Deploy 2026-07-31 lên `x2.fino.vn` — lệnh dán thẳng

> Bổ sung cho `DEPLOY_VPS_UPDATE_RUNBOOK.md` (runbook chung). File này là chuỗi lệnh
> **cụ thể cho đợt 31/07**, dán được ngay.
>
> Tôi **không** SSH được vào VPS nên không tự chạy. Mọi thứ dưới đây tự dò lấy
> `APP_DIR` / DB / site user từ chính server, để không phải điền tay và không điền sai.

---

## BƯỚC 0 — Preflight (chỉ ĐỌC, dán được ngay, không đổi gì)

```bash
ssh <user>@<ip-vps>

# Tìm thư mục app Laravel (CloudPanel đặt ở /home/<siteuser>/htdocs/<domain>)
for d in /home/*/htdocs/*/; do [ -f "$d/artisan" ] && echo "APP: $d"; done
```

Lấy đường dẫn in ra rồi:

```bash
cd <APP_DIR>        # dán đường dẫn vừa tìm được

cat <<'PRE' > /tmp/x2-preflight.sh
set -uo pipefail
echo "── APP_DIR: $(pwd)"
echo "── site user (chủ file): $(stat -c '%U' artisan)"
echo "── PHP: $(php -r 'echo PHP_VERSION;')  | $(which php)"
echo "── Laravel: $(php artisan --version)"
echo
echo "── Git ────────────────────────────────────────────"
git remote -v | head -1
echo "nhánh: $(git branch --show-current)"
echo "đang ở: $(git log --oneline -1)"
echo
echo "── Sửa tay trên server (PHẢI TRỐNG) ──────────────"
git status --short
echo "── hết ──"
echo
echo "── DB đang trỏ ────────────────────────────────────"
grep -E '^DB_(CONNECTION|HOST|DATABASE|USERNAME)=' .env
echo
echo "── Migration CHƯA chạy ────────────────────────────"
php artisan migrate:status 2>/dev/null | grep -i pending || echo "(không có dòng Pending)"
echo
echo "── Bảng kê pending sẽ BIẾN MẤT khỏi app sau deploy ─"
php artisan tinker --execute="echo 'pending: '.DB::table('statements')->where('approval_status','pending')->count().' / tong: '.DB::table('statements')->count().PHP_EOL;" 2>/dev/null \
  || echo "(không chạy được tinker — bỏ qua, không bắt buộc)"
PRE
bash /tmp/x2-preflight.sh
```

**Dừng lại đọc kết quả. Ba điều phải đúng trước khi đi tiếp:**

1. `git status --short` **trống**. Có file lạ = ai đó sửa tay trên server → **đừng chạy
   bước 1**, xử lý chỗ đó trước, vì `git reset --hard` sẽ xoá mất.
2. Nhánh là `main`.
3. Con số `pending` ở dòng cuối = số bảng kê sẽ **biến mất khỏi app cư dân** sau deploy.
   Nếu số đó lớn, **nói với BQL trước** (xem §"Nói gì với BQL" bên dưới).

---

## BƯỚC 1 — Deploy

Dán khối này để ghi script lên server, rồi chạy. Script tự dừng nếu có gì sai — dán từng
lệnh rời thì một lệnh lỗi vẫn chạy tiếp lệnh sau, đó là cách hỏng nửa vời.

```bash
cat <<'DEPLOY' > /tmp/x2-deploy.sh
set -euo pipefail

APP_DIR="$(pwd)"
SITE_USER="$(stat -c '%U' artisan)"
DB_NAME="$(grep -E '^DB_DATABASE=' .env | cut -d= -f2- | tr -d '"'"'"' ')"
DB_USER="$(grep -E '^DB_USERNAME=' .env | cut -d= -f2- | tr -d '"'"'"' ')"

echo "APP_DIR=$APP_DIR"
echo "SITE_USER=$SITE_USER"
echo "DB=$DB_NAME (user $DB_USER)"
echo
read -r -p "Đúng chưa? Enter để tiếp, Ctrl-C để dừng. " _

# ── 1. BACKUP — bắt buộc. Đợt này có 2 migration GHI DỮ LIỆU và không lần lại được:
#    normalize_event_status (down() trống) · normalize_payment_status (KHÔNG có down()).
BACKUP="$HOME/backup-x2bms-$(date +%F-%H%M).sql.gz"
echo "→ Đang dump $DB_NAME ..."
mysqldump -u "$DB_USER" -p "$DB_NAME" | gzip > "$BACKUP"

SIZE=$(stat -c '%s' "$BACKUP")
echo "→ Backup: $BACKUP ($SIZE bytes)"
if [ "$SIZE" -lt 100000 ]; then
  echo "!! BACKUP QUÁ NHỎ ($SIZE bytes) — có thể dump thất bại. DỪNG."
  exit 1
fi
gzip -t "$BACKUP" || { echo "!! Backup hỏng (gzip -t fail). DỪNG."; exit 1; }
echo "→ Backup OK."
echo

# ── 2. Bảo trì: cư dân đang dùng app nhận 503 thay vì lỗi nửa vời
php artisan down --retry=60

# Từ đây trở đi có lỗi thì PHẢI mở lại site, không để treo bảo trì.
trap 'echo "!! LỖI — mở lại site"; php artisan up || true' ERR

# ── 3. Lấy code
git fetch --all
git reset --hard origin/main
echo "→ Đang ở: $(git log --oneline -1)"
composer install --no-dev --optimize-autoloader
# KHÔNG cần npm đợt này: thay đổi 31/07 chỉ là PHP + docs, không đổi asset Filament/Vite.

# ── 4. Migration
echo
echo "── Migration sắp chạy ─────────────────────────────"
php artisan migrate:status | grep -i pending || echo "(không có)"
echo
read -r -p "Đã backup xong ở trên. Chạy migrate? Enter / Ctrl-C. " _
php artisan migrate --force
echo "→ Còn pending (phải trống):"
php artisan migrate:status | grep -i pending || echo "(sạch)"

# ── 5. Cache — BẮT BUỘC. Bỏ bước này thì route mới 404.
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:optimize
php artisan storage:link || true

# ── 6. Quyền + queue. CloudPanel dùng SITE USER, không phải www-data.
chown -R "$SITE_USER":"$SITE_USER" storage bootstrap/cache 2>/dev/null \
  || sudo chown -R "$SITE_USER":"$SITE_USER" storage bootstrap/cache
php artisan queue:restart

# ── 7. Mở lại
trap - ERR
php artisan up
echo
echo "✅ DEPLOY XONG. Backup: $BACKUP"
DEPLOY
bash /tmp/x2-deploy.sh
```

**KHÔNG chạy `php artisan db:seed`.** `DatabaseSeeder` gọi `DemoDataSeeder` — 374 chỗ
`::create(`, gần như không có `firstOrCreate`. Chạy lần hai là **nhân đôi toàn bộ dữ liệu
demo** và không tự dọn được.

---

## BƯỚC 2 — Kiểm tra sau deploy

Chạy trên server (thay `<TOKEN>` bằng token của một cư dân demo, xem
`docs/api/RESIDENT_API_REFERENCE.md`; hoặc bỏ qua và test bằng app trên máy thật):

```bash
# a) Route mới có nạp không (bỏ bước cache là 404 ở đây)
php artisan route:list --path=resident/statements
php artisan route:list --path=resident/payments

# b) Cột mới của đợt này đã có
php artisan tinker --execute="
  echo 'subject_type: '.(Schema::hasColumn('statement_lines','subject_type')?'CO':'THIEU').PHP_EOL;
  echo 'service_period_start: '.(Schema::hasColumn('statement_lines','service_period_start')?'CO':'THIEU').PHP_EOL;
  echo 'payments completed con lai: '.DB::table('payments')->where('status','completed')->count().PHP_EOL;
"
# `payments completed con lai` PHẢI = 0 (normalize_payment_status đã đổi hết sang confirmed)
```

**Kiểm thứ quan trọng nhất của đợt này — cư dân không thấy bảng kê chưa phát hành:**

```bash
curl -s https://x2.fino.vn/api/v1/resident/statements \
  -H "Authorization: Bearer <TOKEN>" \
  -H "X-Device-Id: deploy-check" \
  | python3 -c "import sys,json; d=json.load(sys.stdin)['data']; print('tra ve', len(d), 'ban ghi'); print('published_at rong:', [x['id'] for x in d if not x.get('published_at')] or 'khong co — DUNG')"
```

Dòng cuối phải in `khong co — DUNG`. Nếu in ra id nào thì bản lọc chưa vào (kiểm lại bước
cache).

Trên app máy thật, kiểm nhanh 5 sửa phía app:
1. Bài cộng đồng có trả lời → thấy nút **"Xem N trả lời"**, bấm ra reply, **tải lại vẫn còn**
2. Vào một nhóm → đăng bài → bài **hiện trong nhóm đó**
3. Màn VietQR: câu cuối nói **BQL đối chiếu sao kê**, không hứa tự động
4. Hóa đơn trả một phần: số trên thẻ = **số còn nợ**, khớp với hàng tổng; sheet chọn cổng
   cũng là số còn nợ
5. Sheet chọn cổng: ô cuối **không bị hàng phím điều hướng che**

---

## Nói gì với BQL TRƯỚC khi deploy

> Từ bản này, **cư dân chỉ thấy bảng kê đã phát hành**. Bảng kê còn ở trạng thái chờ duyệt
> sẽ **không hiện trên app** nữa, và công nợ tổng của căn đó giảm tương ứng.
>
> Đây là thay đổi có chủ ý — trước đây app hiện cả bảng kê BQL chưa chốt. **Không mất dữ
> liệu**: vào duyệt và phát hành thì hiện lại ngay, không cần sửa gì.

Con số cụ thể lấy ở dòng cuối của BƯỚC 0.

---

## Rollback nếu hỏng

```bash
php artisan down --retry=60

# 1) Code về commit trước đợt này
git reset --hard <commit-cũ>        # BƯỚC 0 đã in "đang ở: <commit>", dùng đúng cái đó
composer install --no-dev --optimize-autoloader

# 2) DB — KHÔNG dùng migrate:rollback cho đợt này.
#    normalize_event_status có down() TRỐNG, normalize_payment_status KHÔNG có down()
#    ⇒ rollback không lần lại được giá trị cũ. Phải restore từ dump:
gunzip < ~/backup-x2bms-<...>.sql.gz | mysql -u <DB_USER> -p <DB_NAME>

# 3) Cache + mở lại
php artisan optimize:clear
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan filament:optimize
php artisan up
```

**Ngoại lệ:** nếu chỉ muốn bỏ **riêng** migration 31/07 (cột `subject_*` /
`service_period_*` của `statement_lines`) thì cái đó **có `down()` đầy đủ** và đã verify
up → rollback → up trên dev:

```bash
php artisan migrate:rollback --step=1
```

Nhưng bình thường không cần — cột nullable và **chưa có code nào dùng tới** (phần import
khoản phí còn dở, Phase B1).
