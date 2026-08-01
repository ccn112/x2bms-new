# Deploy `x2.fino.vn` (2026-08-01) — lệnh + checklist verify LIVE

> Bổ sung cho `DEPLOY_VPS_UPDATE_RUNBOOK.md` (§1 của runbook đã lạc hậu — dùng bản này
> cho lần deploy 2026-08-01). Mục tiêu: đưa server live lên HEAD hiện tại, rồi **verify
> 5 nhóm resident API đang 🟡** (ví · uploads · bình luận phiếu · articles · weather)
> lên ✅ bằng HTTP thật.
>
> ⚠️ Máy dev **không SSH được VPS** — mọi lệnh §0–§4 chạy trên server. Muốn tôi (Claude)
> đọc kết quả và xử lý tiếp thì chạy qua `! ssh <user>@<vps> '<lệnh>'` để output vào hội thoại.

---

## 0. Xác nhận sự thật trên server (chạy TRƯỚC, một lần)

```bash
ssh <user>@<ip-vps>                       # CloudPanel: site user, không phải root
ls -d /home/*/htdocs/*/ 2>/dev/null       # tìm <APP_DIR> (phải có artisan + public/)
cd <APP_DIR> && ls -1 artisan public composer.json
git remote -v && git branch --show-current && git log --oneline -3
git status --short                        # PHẢI trống — có file lạ = ai đó sửa tay server
php -v && php artisan --version
php artisan migrate:status | grep -i pending    # ← LẤY DANH SÁCH PENDING THẬT, đừng đoán
```

Ghi lại `<APP_DIR>` và `<user>`.

---

## 1. Migration sẽ chạy lần này (đối chiếu với `migrate:status` ở §0)

> Repo có 18 migration từ `2026_07_30` trở đi. **Con số thật = kết quả `grep -i pending`
> ở §0**, không phải bảng này. Bảng này chỉ để đánh giá RỦI RO trước khi bấm migrate.

| Migration | Làm gì | Rủi ro |
|---|---|---|
| `2026_07_30_100000_add_listing_approval_workflow` | cột duyệt tin rao | Thấp (additive) |
| `2026_07_30_100000_normalize_event_status` | `events.status published→upcoming` | ⚠️ **Ghi dữ liệu, `down()` trống** |
| `2026_07_30_100001_ensure_residents_soft_deletes` | bù `deleted_at` | Rất thấp (no-op MySQL) |
| `2026_07_30_150000_add_cancelled_at_to_amenity_bookings` | thêm cột | Thấp |
| `2026_07_30_160000_add_listing_escalation_fields` | đẩy tin rao lên `/sa` | Thấp |
| `2026_07_30_170000_add_resident_payment_claim_fields` | 12 cột luồng cư dân nộp chứng từ | Thấp |
| `2026_07_30_170001_normalize_payment_status` | `payments.status completed→confirmed` | ⚠️ **Ghi dữ liệu, KHÔNG có `down()`** |
| `2026_07_30_180000_create_store_install_stats_table` | bảng mới (telemetry cài đặt) | Thấp |
| `2026_07_30_190000_create_app_telemetry_tables` | 3 bảng telemetry màn hình | Thấp |
| `2026_07_31_100000_add_subject_and_service_period_to_statement_lines` | `subject_*` + `service_period_*` + `due_date` cấp dòng | Thấp — additive, guarded, có `down()` |
| `2026_07_31_200000_add_rollback_fields_to_import_batches` | cột hoàn tác lô import | Thấp |
| `2026_07_31_210000_convert_import_batch_row_type_to_string` | `import_batch_rows.row_type` ENUM→string (drop→copy→rename cột) | ⚠️ **Đổi cấu trúc cột trên dữ liệu cũ, `down()` no-op** — backup bắt buộc |
| `2026_07_31_300000_community_group_hierarchy` | mở rộng `community_groups` (group_type/slug/scope…) | Thấp — additive |
| `2026_07_31_310000_create_user_project_follows` | bảng follow dự án | Thấp |
| `2026_07_31_400000_add_maker_checker_fields_to_statements` | cột duyệt bảng kê | Thấp |
| `2026_08_01_000001_add_payment_priority_lock_to_fee_types` | `fee_types.payment_priority(_locked_at)` | Thấp |
| `2026_08_01_000002_create_fee_type_priority_overrides` | bảng override ưu tiên theo dự án | Thấp |
| `2026_08_01_100000_create_community_membership_grants` | bảng grant membership + `community_group_members.resident_id` NULLABLE | Thấp — additive |

**3 migration ghi/đổi dữ liệu, `down()` yếu → BẮT BUỘC backup DB ở §2 trước khi migrate.**

---

## 2. Backup DB (BẮT BUỘC, không bỏ qua)

```bash
cd <APP_DIR>
# đọc DB name/user từ .env (KHÔNG in password ra log dùng chung)
DB_NAME=$(grep -E '^DB_DATABASE=' .env | cut -d= -f2)
mysqldump -u <db_user> -p "$DB_NAME" | gzip > ~/x2_backup_pre_20260801.sql.gz
ls -lh ~/x2_backup_pre_20260801.sql.gz          # xác nhận file > 0 byte
```

---

## 3. Deploy

```bash
cd <APP_DIR>
git pull
composer install --no-dev --optimize-autoloader
php artisan down --render="errors::503" || true     # tuỳ chọn: maintenance mode
php artisan migrate --force
php artisan db:seed --class=ApartmentWalletDemoSeeder    # ví căn hộ (demo)
php artisan db:seed --class=ResidentArticleSeeder        # articles quy định/cẩm nang
php artisan storage:link                                  # BẮT BUỘC cho uploads (disk public)
php artisan optimize:clear
php artisan up
```

### 3b. Backfill dữ liệu (chạy `--dry-run` trước, rồi chạy thật)

> Các lệnh này chuẩn hoá dữ liệu cũ theo quyết định mới (family phí, ưu tiên phân bổ,
> follow dự án, membership grant). Idempotent — chạy lại an toàn. Trên DB dev đều đã chạy.

```bash
php artisan billing:backfill-fee-family --dry-run   && php artisan billing:backfill-fee-family
php artisan billing:backfill-fee-priority --dry-run && php artisan billing:backfill-fee-priority
php artisan community:backfill-project-follows --dry-run   && php artisan community:backfill-project-follows
php artisan community:backfill-membership-grants --dry-run && php artisan community:backfill-membership-grants
```

---

## 4. Verify LIVE — 5 nhóm resident API (đưa 🟡 → ✅)

**Chuẩn bị token** (account demo có dữ liệu đầy đủ nhất — res 1305):

```bash
BASE=https://x2.fino.vn/api/v1
TOKEN=$(curl -s -X POST $BASE/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"identifier":"nguyenvananh@gmail.com","password":"Resident@2026!"}' \
  | python3 -c 'import sys,json;print(json.load(sys.stdin)["data"]["token"])')
AUTH="Authorization: Bearer $TOKEN"
echo "token len: ${#TOKEN}"     # > 0 là login OK
```

| # | Nhóm | Lệnh | Kỳ vọng ✅ |
|---|---|---|---|
| 1 | **Ví — số dư** | `curl -s $BASE/resident/wallet -H "$AUTH"` | 200, có `balance` + mảng ngăn 2 cấp (fee_category→fee_type) |
| 2 | **Ví — giao dịch** | `curl -s $BASE/resident/wallet/transactions -H "$AUTH"` | 200, mỗi dòng có `direction` + `type` (KHÔNG phải bảng `wallet_transactions` công ty) |
| 3 | **Uploads** | `curl -s -X POST $BASE/resident/uploads -H "$AUTH" -F "file=@/path/anh.jpg"` | 201/200, trả `attachment id` + URL mở được (đã `storage:link`) |
| 4 | **Bình luận phiếu — đọc** | `curl -s "$BASE/resident/payments/<id>/comments" -H "$AUTH"` | 200, chỉ phiếu thuộc `apartment_id` của mình; resource khác whitelist → 404 |
| 5 | **Bình luận phiếu — ghi** | `curl -s -X POST "$BASE/resident/visitor-registrations/<id>/comments" -H "$AUTH" -d '{"body":"test"}'` | 201; phiếu không thuộc căn mình → 403/404 |
| 6 | **Articles — list** | `curl -s $BASE/resident/articles -H "$AUTH"` | 200, có bài (đã seed `ResidentArticleSeeder`); phân loại quy định/cẩm nang/tin tức |
| 7 | **Articles — detail** | `curl -s $BASE/resident/articles/<id> -H "$AUTH"` | 200, thân bài HTML |
| 8 | **Weather** (trong Home) | `curl -s $BASE/resident/home -H "$AUTH"` | 200, khối `weather` có nhiệt độ/AQI (proxy Open-Meteo) — không lỗi 5xx |

**Ghi chú whitelist bình luận phiếu:** `resource` chỉ nhận `visitor-registrations` · `payments`
· `amenity-bookings` (route `whereIn`), và `id` phải là số.

**Gate cư dân (kiểm tra kèm — D1):** `curl -s $BASE/resident/statements -H "$AUTH"` chỉ được
trả bảng kê **đã phát hành** (`approval_status=published` VÀ `published_at` khác null).
Bảng kê `pending` phải KHÔNG xuất hiện; gọi chi tiết một bảng kê chưa phát hành → **404**
(không phải 403).

---

## 5. Sau khi verify xong

1. Cập nhật `docs/PROGRESS_TRACKER.md` §4a: 5 nhóm 🟡 → ✅ kèm ngày verify HTTP LIVE.
2. Cập nhật `x2mobile/PROGRESS_TRACKER.md` mục 13: bỏ "việc chặn duy nhất" (deploy đã xong).
3. Ghi `DEV_JOURNAL.md` cả hai repo.
4. Soi app trên **điện thoại thật**: build `-t lib/main_prod.dart` (thiếu flavor prod →
   app báo "lỗi kết nối mạng"). Trỏ `X2_API_BASE_URL=https://x2.fino.vn/api/v1`.

---

## 6. Rollback nếu hỏng

- Migration lỗi giữa chừng: `php artisan migrate:rollback --step=1` **chỉ an toàn với các
  migration additive**. Ba migration ⚠️ ở §1 có `down()` trống/no-op → **phục hồi bằng
  backup §2**: `gunzip < ~/x2_backup_pre_20260801.sql.gz | mysql -u <db_user> -p <DB_NAME>`.
- Code lỗi: `git reset --hard <commit_cũ> && composer install --no-dev -o && php artisan optimize:clear`.
