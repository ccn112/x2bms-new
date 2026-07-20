# X2-BMS — Session Handoff (2026-07-20)

> Phiên này làm **tầng Import/Export dùng chung 3 tầng (SA/HQ/BQL)** + **import cư dân BQL** (UI wizard + file mẫu). Đã commit & push `main`. Đọc file này để tiếp tục trên máy khác.
> Không chứa secret — `.env` (DB/mail/APP_KEY…) phải mang sang máy mới qua kênh an toàn, KHÔNG qua git.

---

## 1. Trạng thái git
- Repo `ccn112/x2bms-new`, nhánh **`main`**, đã push: **`195e5b1`** — *feat(import): nền import/export dùng chung 3 tầng + import cư dân BQL* (12 files, +1024/−19).
- Trên máy mới: `git pull origin main`.

## 2. Đã làm phiên này (chi tiết: `docs/DEV_JOURNAL.md`, 3 entry ngày 2026-07-20)
**Increment 1 — nền dùng chung (panel-agnostic, port pattern x1web, độc lập gói Excel — x2bms dùng `spatie/simple-excel`, KHÔNG kéo `maatwebsite`):**
- `app/Support/Import/RowNormalizers.php` (string/email/phone/date + header + value guess-match), `RowIssue.php`, `ImportSummary.php`.
- `app/Support/Export/ExportsCsv.php` (trait `streamCsv` + BOM UTF-8). `ResidentDirectory::export()` đã refactor dùng trait.

**Increment 2 — engine staging + profile cư dân:**
- Migration ADD-ONLY `database/migrations/2026_07_20_000001_extend_import_batches_for_residents.php`: `import_batches.import_type` += `residents`, `import_batch_rows.row_type` += `resident`, thêm `import_batches.building_id` nullable.
- `app/Support/Import/ImportColumnSpec.php`, `ImportProfile.php`, `StagingImporter.php` (`stage()` đọc file→tạo batch+rows normalized+validate; `commit()` ghi dòng valid|warning qua profile).
- `app/Support/Import/Profiles/ResidentImportProfile.php`: 11 cột thật của `residents`; bọc **scope tenant/building + audit + dedup** qua `ResidentIdentityMatcher` (vá điểm single-tenant của x1web). `source='import'`, `profile_status='cho_bo_sung'` (giá trị đã ghi trong migration, không tự chế).

**Increment 3 — UI:**
- `app/Filament/Concerns/ImportsResidentsFromExcel.php`: modal 2 bước (chọn tòa + upload → `stage()` → preview đếm/bảng từng dòng → `commit()`), + action **"Tải file mẫu"** (`downloadResidentImportTemplate()` sinh xlsx từ `columns()`). Nối vào `ResidentDirectory` (thay nút import stub + thêm nút tải mẫu).

## 3. Verify đã chạy
- `php -l` sạch mọi file; `view:cache` compile OK.
- **Engine E2E trên DB thật** (có dọn sạch): CSV 3 dòng → stage `valid=2 error=1` (bắt đúng dòng thiếu CCCD), normalizer đúng (`0901-234 567`→`0901234567`, `15/01/1990`→`1990-01-15`), commit tạo 2 resident mã unique, batch→`committed`.
- **Livewire test:** `mountAction('residentImport')` mount modal OK (cùng cơ chế action `edit`/`resetPassword` đang chạy production).
- **File mẫu** đã sinh: `storage/app/public/mau_import_cu_dan.xlsx` (link `/storage/mau_import_cu_dan.xlsx`; file này KHÔNG commit vì `storage/` gitignore — tái tạo bằng nút "Tải file mẫu").

## 4. Bẫy môi trường máy D: (LƯU Ý khi setup máy mới)
- `composer install` **fail trên Windows** do `laravel/horizon`+`laravel/octane` cần `ext-pcntl`/`ext-posix` (chỉ Linux). Cách chạy được local:
  `composer install --ignore-platform-reqs` (queue/octane không dùng ở local nên không sao).
- Nếu artisan báo `Class "Laravel\Octane\Octane" not found` → do thiếu `vendor/laravel/octane` (composer install chưa đủ) → chạy lệnh trên.
- Sau composer install: `php artisan migrate` (áp migration mới), và nếu modal/asset Filament lỗi thì `php artisan filament:assets`.
- **Preview browser in-app KHÔNG render modal Filament v5** (kiểm chứng: action `edit` sẵn có cũng không mở) → click-through import phải test trên **Chrome/Edge thật**.

## 5. Chạy & test nhanh
```powershell
php artisan serve --host=127.0.0.1 --port=8123
# đăng nhập /admin: nv1@x2bms.vn / Bms@2026!  (BQL, tenant=1, building=1)
# /admin/residents → "Tải file mẫu" → sửa dữ liệu → "Nhập dữ liệu" (chọn tòa + upload) → preview → "Ghi các dòng hợp lệ"
```

## 6. Việc còn lại (đề xuất ưu tiên)
1. **Async + nhật ký xử lý** (owner yêu cầu — x1 làm tốt: `ImportLog` + Filament queued Import/Export): đưa `commit()` (và export lớn) vào **Job (queue database)**; thêm màn **"Nhật ký nhập liệu"** đọc `import_batches` (status/counts/created_by/committed_at — đã là log sẵn); thông báo khi job xong. Cân nhắc thêm status `processing`.
2. **Click-through browser thật** xác nhận upload→preview→commit.
3. **i18n message validate** (hiện tiếng Anh "The id no field is required.").
4. **Áp profile cho HQ/SA** khi cần (engine đã dùng chung).
5. Tính năng import cho các màn khác (căn hộ, xe/thẻ…) bằng cách viết thêm `ImportProfile`.

## 7. Bản đồ tài liệu
| File | Nội dung |
|---|---|
| `docs/DEV_JOURNAL.md` | Nhật ký (3 entry 2026-07-20 mới nhất) |
| `docs/BQL_MASTER_BUILD_PLAN_20260703.md` | Kế hoạch 30 màn BQL (import wizard dùng chung nằm trong Đợt B) |
| bộ nhớ Claude: `x1web-reusable-filament-for-x2bms` | Pattern x1web + caveat khi port |

*Handoff 2026-07-20. Commit/branch chính xác xem `git log`.*
