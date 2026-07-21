# X2-BMS — Nhật ký phát triển (Dev Journal)

Mỗi lần cập nhật code, ghi một entry vào đầu danh sách (mới nhất ở trên).
Định dạng: ngày · phạm vi · file đổi · tóm tắt · cách verify.

---

## 2026-07-21 — Slice 0 (mobile auth) verify E2E thật + chốt contract

**E2E thật (flutter test → backend live 8123, tài khoản cư dân `0900000555`):** login→tokens ✅ · sai mật khẩu→Err ✅ · otp/request→challenge ✅ · me/bootstrap(Bearer)→200 ✅. **Chạy code mobile thật** (`RemoteAuthRepository` + `ApiClient` x2mobile), không mock.

**Bug đã bắt & fix (x2mobile):** Dio nối `baseUrl` thiếu `/` cuối + path `auth/login` → **rớt `/v1`** → 404 mọi API. Fix trung tâm `ApiClient` (tự thêm `/` cuối baseUrl). Chỉ lộ khi chạy remote thật lần đầu.

**Contract Slice 0 (auth):** đóng băng ở `docs/guide/mobile-api-usage.md §10` (mẫu login đã verify, map OTP channel/destination/purpose, cảnh báo baseUrl, trạng thái wiring: RemoteAuthRepository ✅ / onRefresh ⬜ / register ⬜, tài khoản test). **Copy sang handoff dùng chung:** `handoff/x2bms/_contracts/mobile-api-usage.md`.

**x2mobile (repo riêng) — đổi:** `RemoteAuthRepository` (nối thật), `ApiClient` (baseUrl fix), `test/live_auth_test.dart` (E2E). Chưa commit — chờ chốt điều phối agent.

---

## 2026-07-21 — Track A: đóng cụm cư dân Đợt B (4 màn) + vá hiệu năng Hộ gia đình

**Đánh giá thực tế (render Livewire):** cả 4 màn ĐÃ hoạt động (không phải stub rỗng) — có getViewData + blade + KPI + filter:
- `ResidentTimeline` (Dòng thời gian) ✓ · `MoveInOutHistory` (Chuyển đến/đi) ✓ (hiển thị cap 120) · `ResidentDataQuality` (Chất lượng DL) ✓ (dùng aggregate COUNT — chuẩn).
- `HouseholdRelationships` (Hộ gia đình) ✗ **nạp toàn bộ ~1300 quan hệ, không phân trang → HTML 3.66MB**.

**Vá HouseholdRelationships:** `WithPagination` — phân trang **theo căn hộ** (24/trang, scope tòa, filter role/search qua whereHas), chỉ nạp quan hệ của căn ở trang hiện tại; KPI chuyển sang **aggregate COUNT** (distinct apartment_id / is_primary) thay vì nạp hết. Blade: hiện tổng số hộ + `->links()`. Reset page khi đổi search/role. **Kết quả: 3.66MB → 98KB (×37).**

**Nav:** 4 màn nest dưới cha "Cư dân" (navigationParentItem).

**Verify:** `php -l` sạch; render lại 4 màn OK, kích thước bounded (Household 98KB, Timeline 120KB, Move 178KB, DataQuality 78KB).

**Còn lại (tính năng nâng cao, tùy chọn — không chặn):** MoveInOutHistory state machine (confirm/cancel/correct); ResidentDataQuality action fix/merge/gửi yêu cầu cập nhật per BQL plan. Hiện cả hai là màn tra cứu/dashboard read-only chạy tốt.

---

## 2026-07-21 — Fix nav: `->icon()` trên NavigationGroup LÀM PHẲNG sub-nav (bỏ icon → nesting trở lại)

**Triệu chứng:** "Cây căn hộ"/"Duyệt gắn căn hộ" hiện phẳng thay vì thụt dưới cha "Hồ sơ căn hộ". **Nguyên nhân:** thêm `->icon()` cho `NavigationGroup` khiến Filament render nhóm ở layout khác, **mất `.fi-sidebar-sub-group`** (kiểm chứng qua DOM: subGroupCount 0→1 sau khi bỏ icon). **Fix:** bỏ icon group ở AdminPanelProvider (trả về `NavigationGroup::make('...')` trần) → nesting hiện lại đúng "như cũ". Hq cũng trả về nguyên bản (chỉ giữ icon gốc X2 AI Engine). SA giữ icon gốc (không dùng navigationParentItem nên không ảnh hưởng). Verify browser thật: "Cây căn hộ" thụt lề dưới "Hồ sơ căn hộ". (Lưu ý: "Duyệt gắn căn hộ" ẩn với tài khoản nv1 do phân quyền — hiện đủ với tài khoản có quyền/platform admin, không phải lỗi nav.)

**Bẫy ghi nhớ:** KHÔNG đặt `->icon()` cho NavigationGroup nếu nhóm đó chứa mục có `navigationParentItem` (sẽ phá nesting).

---

## 2026-07-21 — Nav: bỏ collapsed (giữ sub-nav nested như cũ) + icon nhóm + nhóm SA "Lưu trữ & Sao lưu"

**Đính chính (owner):** "sub-nav" = **menu lồng** đã có sẵn qua `navigationParentItem` (vd cha **"Hồ sơ căn hộ"** → con **Cây căn hộ**, **Duyệt gắn căn hộ**; cha "Cư dân" → ResidentDetail). Lần trước tôi đặt `collapsed()` → che mất mục con. Đã **bỏ `collapsed()`** cả 3 panel để mục con hiện lại "như cũ"; **giữ nguyên 1 nhóm "Cư dân & Căn hộ"** (không tách). Thêm icon cho group header (mỹ thuật). Giữ nhóm SA mới **"Lưu trữ & Sao lưu"** (2 màn tenant). Verify: dump nav /admin thấy đúng cây cha→con, `php -l` sạch.

---

## 2026-07-21 — Fix 500 SA backup/lifecycle: closure column phải nhận `$state`

`/sa/tenant-backups` báo `BindingResolutionException: [$s] unresolvable` — column closure Filament resolve theo TÊN tham số. Đổi `formatStateUsing/color/state` từ `$s`/`$b` → **`$state`**, record closure → **`$record`** (cả TenantBackupManager + TenantLifecycleManager). Verify: render thật 2 page (có dữ liệu) dưới platform admin → OK. (Bẫy đã ghi ở BQL_MASTER_BUILD_PLAN §"bẫy đã trả giá".)

---

## 2026-07-21 — SA UI vòng đời tenant + registry backup + chặn login dormant + sweep + schema-drift (Increment 10)

**Vòng đời tenant (state machine):** migration `tenants` += `lifecycle_status` (active|dormant_archived|purged) + `dormant_at` + `retention_until`. Offboard → dormant_archived + retention (config `retention_days`, mặc định ~3 năm). Restore → active.

**Chặn đăng nhập:** `User::canAccessPanel` — platform admin luôn vào; nhân sự tenant `dormant_archived`/`purged` bị chặn.

**Registry backup:** bảng `tenant_backups` + model + `TenantBackupService` ghi sổ mỗi lần tạo (path/size/counts/file_count/app_version/trigger/created_by). `latestBundle` ưu tiên registry.

**SA UI (2 page, panel /sa):**
- `TenantLifecycleManager` (`/sa/tenant-lifecycle`): bảng tenant + trạng thái, actions **Sao lưu / Off & lưu trữ / Khôi phục** (có confirm + cảnh báo).
- `TenantBackupManager` (`/sa/tenant-backups`): bảng bản backup + **Tải về / Xóa** (retention).

**Sweep tự động** (`tenant:lifecycle-sweep`, mặc định dry-run, `--commit` để chạy): active + thuê bao hết hạn quá `grace_days` → OFF; dormant quá `retention_until` → PURGE (xóa bundle + đánh dấu purged). Tiering nóng→lạnh = **lifecycle policy của bucket S3** (cấu hình hạ tầng khi mua, ghi chú trong command) — local không có tier.

**Schema-drift khi rehydrate (bundle cũ):** `TenantRestoreService` chỉ chèn cột CÒN tồn tại ở schema hiện tại (`array_intersect_key` với `getColumnListing`); cột mới nhận default. Manifest có `app_version` làm mốc.

**Verify (E2E tenant tạm, xóa sạch sau):** setup active → backup (registry=1) → ACCESS active(admin=true) → OFF (dormant, retention 2029, purged data, bundle còn) → ACCESS dormant(non-admin=false, admin=true) → RESTORE (active, data+file đúng nội dung) → registry download/delete OK → sweep dry-run OK. migrate DONE, `php -l` sạch 8 file.

---

## 2026-07-21 — Rehydrate/restore + offboard (churn off↔restore) (Increment 9)

**Đối xứng backup:** hoàn thiện vòng đời dữ liệu tenant.
- `app/Support/Backup/TenantRestoreService.php`: rehydrate từ bundle .zip — nạp DB (NDJSON, **giữ nguyên id**; xoá dòng tenant hiện có reverse-order rồi chèn forward-order, bọc transaction + `SET FOREIGN_KEY_CHECKS=0/1`) + đẩy file `files/<key>` về vùng tenant. `latestBundle()` chọn bundle mới nhất. Kiểm manifest khớp tenant.
- `app/Support/Backup/TenantOffboardService.php`: **off** (churn) = backup trước → purge DB rows (reverse, FK off) + purge files **NHƯNG giữ `_backups/`** (đúng mô hình "gói storage": off vẫn giữ hộ bundle, resume thì rehydrate).
- Command: `php artisan tenant:offboard {tenant}` (có xác nhận/`--force`), `php artisan tenant:restore {tenant} [--bundle=]`.

**Verify (E2E trên TENANT TẠM cô lập, không đụng tenant demo, xóa sạch sau test):**
TRƯỚC residents=2/buildings=1/projects=1/file=có → OFF: purge 4 rows + 1 file, mọi thứ=0, file mất, **bundle còn** → RESTORE: khôi phục 4 rows + 1 file, counts về đúng, **file đúng nội dung** → ✅ PASS. `php -l` sạch.

**Còn lại:** UI trigger backup/restore + màn quản lý bản backup (retention/tải/xóa); subscription state machine + tiering nóng→lạnh tự động; schema-migration khi rehydrate bundle cũ hơn version hiện tại (manifest đã có `app_version`).

---

## 2026-07-21 — Backup bundle theo tenant + route KYC/kb qua TenantStorage (Increment 8)

**(item 3) Route KYC + kb-attachments qua TenantStorage (per-tenant folder, tương thích ngược):**
- KYC (`ResidentForm`): 3 FileUpload id_front/id_back/portrait → disk theo ENV + `tenants/{t}/residents/kyc` (giữ visibility private). `PrivateMediaController` đọc qua `TenantStorage::disk()` (S3-ready; disk local nên file cũ vẫn serve).
- kb (`AiKnowledgeBase`): FileUpload attachments → tenant disk + `tenants/{t}/kb-attachments` (rời disk 'public' → riêng tư hơn). `DocumentTextExtractor::fromStoredFile` đọc qua `TenantStorage::localReadablePath` + **fallback disk 'public'** cho file cũ.

**(item 2) Backup/export bundle theo tenant:**
- `config/tenant-backup.php`: danh sách bảng tenant-scoped (tự bỏ bảng không có `tenant_id`) + chunk.
- `app/Support/Backup/TenantBackupService.php`: dump DB **NDJSON** (lọc `tenant_id`, cursor chunk) + gom toàn bộ file vùng tenant → **.zip** (`manifest.json` có `app_version` để rehydrate bản cũ · `db/*.ndjson` · `files/<key>`), lưu tại `tenants/{t}/_backups/{ts}/backup.zip` (chính trong vùng tenant → hợp mô hình "gói storage"). Backup LOGIC, độc lập DB engine.
- `php artisan tenant:backup {tenant}` (dùng tay/lên lịch).

**Verify:** `php -l` sạch; KYC/kb lint OK; `tenant:backup 1` → zip 95KB, 13 entry (manifest + 12 NDJSON, counts đúng theo tenant), section files/ hoạt động; dọn sạch.

**Còn lại (đợt sau):** UI trigger backup + màn quản lý bản backup (tải/xóa/retention); **rehydrate/restore** từ bundle; subscription state machine + tiering nóng→lạnh (kịch bản churn); route nốt các điểm upload khác (marketplace/platform content) nếu cần theo tenant.

---

## 2026-07-20 — Nền lưu trữ đa tenant (TenantStorage) — folder riêng từng tenant, ENV-ready S3 (Increment 7)

**Bối cảnh (owner):** SaaS, dữ liệu upload lưu **folder riêng từng tenant/dự án** để sau backup được; tạm local, **cấu hình S3/SA đặt ở ENV** (mua sau chỉ điền ENV, không sửa code); giữ **shared DB + tenant_id** (silo tương lai).

**File mới:**
- `config/tenant-storage.php`: `disk` = `env('TENANT_STORAGE_DISK','local')`, `root_prefix` = `env('TENANT_STORAGE_ROOT','tenants')`.
- `app/Support/Storage/TenantStorage.php`: cổng I/O DUY NHẤT cho file tenant. Prefix lấy từ `CurrentContext` → `tenants/{tenant_id}/projects/{building_id}/<relative>` (chống rò chéo). Driver-agnostic: `prefix()/key()/move()/exists()/download()` + `localReadablePath()` (local→path thật; remote→tải file tạm, để lib đọc Excel theo path vẫn chạy khi lên S3).
- `.env.example`: thêm `TENANT_STORAGE_DISK`/`TENANT_STORAGE_ROOT` + `AWS_URL`/`AWS_ENDPOINT` (MinIO), có chú thích "mua thì điền".

**Đổi:** import cư dân — FileUpload nay đẩy vào `tenants/{t}/_incoming/residents` (disk theo ENV); sau stage **move** file nguồn về `tenants/{t}/projects/{b}/residents/import/{batch}/` + cập nhật `storage_path`. `ImportHistory` tải file nguồn qua `TenantStorage` (mọi driver).

**Verify (DB thật, dọn sạch):** disk=local/root=tenants; upload→incoming→stage đọc OK→move ra `tenants/1/projects/1/residents/import/{id}/…` (file thật trên đĩa), incoming đã xóa; commit tạo cư dân. `php -l` sạch.

**Chưa làm (đã bàn, owner OK — đợt sau):** backup/export bundle theo tenant; subscription state machine + tiering nóng→lạnh + rehydrate (kịch bản 1 năm on → 2 năm off gói storage → year 3 on); route nốt KYC/kb-attachments qua TenantStorage.

---

## 2026-07-20 — Import async (queue) + màn Nhật ký Import/Export + retry (Increment 6 / Đợt 2)

**Async:** ghi (commit) chuyển sang **hàng đợi nền**:
- `app/Jobs/CommitImportBatchJob.php` (ShouldQueue): set `committing` → `StagingImporter::commit()` (tự set committed/failed). **Idempotent** (chỉ xử lý dòng valid|warning, dòng 'imported' bỏ qua) → **retry an toàn, không trùng**.
- `app/Support/Import/ImportProfileRegistry.php`: map `import_type` → ImportProfile (để Job dựng lại profile).
- Migration `2026_07_20_000002`: `import_batches.status` += `committing`.
- Popup preview "Ghi các dòng hợp lệ" nay **dispatch Job** + set `committing` + báo "đưa vào hàng đợi, theo dõi ở Nhật ký". (Cần chạy `php artisan queue:work` — QUEUE_CONNECTION=database.)

**Màn Nhật ký Import/Export** — `app/Filament/Pages/ImportHistory.php` (+ blade), nav "Cư dân & Căn hộ", slug `/admin/import-history`:
- Bảng `import_batches` scope theo tòa: thời gian, file nguồn, loại, **badge trạng thái**, tổng/hợp lệ/lỗi, người tạo, ghi lúc.
- Row actions: **Chi tiết** (bảng từng dòng + lỗi), **Tải file nguồn** (kiểm tra file người dùng upload), **Nhập lại (retry)** dòng còn lại (re-dispatch Job), **Export kết quả** (CSV cư dân đã tạo bởi batch — đối chiếu dữ liệu đã lưu, dùng trait ExportsCsv).

**Verify (DB thật, dọn sạch):** stage valid=2 → `dispatchSync` Job → `committed` + committed_at + 2 cư dân (SĐT tự chuẩn hóa); chạy lại Job → vẫn 2 (idempotent). `ImportHistory` mount OK (Livewire::test assertOk). `php -l` sạch; migrate DONE.

**Ghi chú:** modal action (upload/preview/history) render qua **table/preview** → hoạt động trên trình duyệt thật; queue cần worker chạy nền.

---

## 2026-07-20 — Làm sạch dữ liệu nhập (normalizer mạnh) + cảnh báo chất lượng (Increment 5)

**`RowNormalizers` nâng cấp** (nền dùng chung mọi profile/tầng):
- `stripInvisible()`: quy đổi nbsp/zero-width/BOM/tab/newline → space.
- `string()`: gộp mọi khoảng trắng (kể cả ẩn) → 1 space (sửa "2 dấu cách giữa họ tên").
- `name()` (mới): whitespace + **Title Case unicode** ("nguyễn  văn AN"/"TRẦN THỊ BÌNH" → "Nguyễn Văn An"/"Trần Thị Bình").
- `email()`: bỏ **mọi** khoảng trắng kể cả ở giữa ("a b c@x.vn"→"abc@x.vn") + lowercase.
- `phone()`: bỏ ký tự thừa; `+84`/`84`/`0084`→`0`; **mất số 0 đầu do Excel** (9 số bắt đầu 3/5/7/8/9) → thêm `0` ("090 1234 567"/"901234567"/"+84 901 234 567" → "0901234567").
- `idNo()` (mới): chỉ giữ chữ số; **CCCD mất số 0 đầu** (11 số) → pad về 12.

**`ResidentImportProfile`:** full_name→`name`, id_no→`idNo`; **bỏ rule email cứng** (không chặn dòng); thêm **cảnh báo chất lượng** (rule-based) sau chuẩn hóa: CCCD ≠ 9/12 số, SĐT ≠ dạng `0#########`, email sai định dạng (vẫn lưu, gợi ý bổ sung).

**Verify:** `php -l` sạch; test 12 case dữ liệu bẩn (nbsp, mất số 0, dấu cách giữa email/tên/CCCD, +84) → ra đúng kỳ vọng.

---

## 2026-07-20 — Import gộp căn+cư dân+quan hệ · nới lỏng required + gợi ý AI · mẫu trong popup (Increment 4)

**Quyết định owner (2026-07-20):** (1) chỉ **Họ tên** bắt buộc cứng; thiếu CCCD/SĐT/Email vẫn nhập, tự đặt `profile_status='cho_bo_sung'` + cảnh báo + gợi ý AI (rule-based). (2) **Gộp 1 file** tạo căn hộ + cư dân + quan hệ.

**`ResidentImportProfile` (rework):**
- Cột: bỏ `required` ở id_no/phone (chỉ full_name required); **thêm `Mã căn hộ` + `Vai trò`** (normalizeRole: Chủ sở hữu/Người thuê/Thành viên → owner/tenant/member).
- `validateRow`: gợi ý AI theo loại thiếu (CCCD → chờ bổ sung; thiếu SĐT+Email → chưa kích hoạt; trùng CCCD trong tòa → gộp; căn chưa có → sẽ tạo mới; không mã căn → chưa gắn). Tất cả **warning** (không chặn), chỉ thiếu Họ tên = error.
- `commitRow`: `profile_status` = `cho_bo_sung` nếu thiếu định danh, ngược lại `hoat_dong`; **resolve-or-create Apartment theo mã trong tòa** + tạo `ResidentApartmentRelation` (role/is_primary/start_date); tự liên kết tài khoản; audit ghi cả căn + vai trò.

**UI (item 1):** link **"Tải file mẫu (.xlsx)"** chuyển VÀO footer popup import (`extraModalFooterActions`), **gỡ nút header** ngoài. Mô tả modal cập nhật (required nới lỏng + cột gộp). File mẫu nay 13 cột (thêm Mã căn hộ, Vai trò).

**Verify (E2E DB thật, có dọn sạch):** CSV 3 dòng → stage total=3 valid=2 error=1. row đủ→`hoat_dong`+tạo căn+quan hệ owner; row chỉ-tên+căn→`cho_bo_sung`+tạo căn+quan hệ tenant + đủ gợi ý AI; row thiếu tên→error (bỏ qua, KHÔNG tạo căn). commit created=2. `php -l` sạch.

**Còn lại (đợt sau — item 3+4 owner yêu cầu):** async queue (đưa `commit` vào Job) + màn **Nhật ký Import/Export** (status/counts/retry dòng lỗi/xem-tải file nguồn) + export dữ liệu đối chiếu.

---

## 2026-07-20 — UI wizard nhập cư dân (Increment 3)

**Phạm vi:** nối engine staging vào giao diện BQL — nút "Nhập dữ liệu" ở `ResidentDirectory` (trước là stub) nay chạy thật qua modal 2 bước.

**File mới:** `app/Filament/Concerns/ImportsResidentsFromExcel.php` (trait):
- `residentImportAction()` (bước 1): modal chọn **Tòa/dự án** (Select scope theo `CurrentContext::buildings`) + **FileUpload** .xlsx/.csv (disk `local`, thư mục `imports/residents`) → `StagingImporter::stage()` → `replaceMountedAction('residentImportPreview')`.
- `residentImportPreviewAction()` (bước 2, auto-discover): modal 4xl hiện **đếm tổng/hợp lệ/lỗi** + **bảng từng dòng** (Họ tên/CCCD/SĐT/trạng thái/ghi chú lỗi, tối đa 200 dòng) → nút "Ghi các dòng hợp lệ" gọi `StagingImporter::commit()` + audit `resident.import` + notification + `refreshTable()`. Chặn khi `valid_rows=0`.
- Context ghi theo `tenant_id` (user) + `building_id` (chọn ở form) → scope đúng, không rò cross-tenant.

**Sửa:** `ResidentDirectory` — `use ImportsResidentsFromExcel`, thay action stub bằng `$this->residentImportAction()`.

**Verify:** `php -l` sạch 2 file; `php artisan view:cache` compile OK (blade/HtmlString hợp lệ); boot app dựng được cả 2 action (`residentImport`/`residentImportPreview`), header actions = `residentImport, export, create` (stub đã thay). **Còn lại — verify browser thật:** click upload→preview→commit trên `/admin/residents` với phiên BQL (chưa chạy trong phiên này do cần đăng nhập panel). Pipeline lõi đã verify E2E ở Increment 2.

---

## 2026-07-20 — Engine staging import + profile cư dân (Increment 2)

**Phạm vi:** engine import staging DÙNG CHUNG 3 tầng trên bảng `import_batches`/`import_batch_rows` sẵn có + profile import cư dân BQL. Verify end-to-end thật.

**Schema (delta ADD-ONLY):** `database/migrations/2026_07_20_000001_extend_import_batches_for_residents.php` — `import_batches.import_type` += `residents`, `import_batch_rows.row_type` += `resident`, thêm `import_batches.building_id` nullable FK. An toàn trên bảng đã seed. `migrate` DONE.

**File mới:**
- `app/Support/Import/ImportColumnSpec.php` — VO mô tả cột (key/label/aliases/required/normalizer/rules/example) + `extract($row)`; tương đương Filament `ImportColumn` nhưng độc lập UI/package.
- `app/Support/Import/ImportProfile.php` — interface nghiệp vụ (importType/rowType/columns/validateRow/commitRow).
- `app/Support/Import/StagingImporter.php` — engine: `stage()` (đọc file bằng spatie/simple-excel → tạo batch + rows raw+normalized, validate field bằng Validator + rule nghiệp vụ, đếm, status=validated) và `commit()` (ghi dòng valid|warning qua profile, set committed_entity, batch=committed/failed). Không tự biết tenant/building — nhận qua `$context`.
- `app/Support/Import/Profiles/ResidentImportProfile.php` — 11 cột thật của `residents`; `validateRow` cảnh báo trùng CCCD trong tòa + đã có tài khoản X2BMS; `commitRow` set scope tenant/building + code unique + `source='import'`/`profile_status='cho_bo_sung'` (giá trị đã ghi trong migration, không tự chế) + tự liên kết `user_id` qua `ResidentIdentityMatcher` + ghi `AuditLog`.

**Verify (end-to-end, DB thật, có dọn sạch):** CSV 3 dòng → STAGE `total=3 valid=2 error=1` (dòng thiếu CCCD bị bắt đúng "id no required"); normalizer áp đúng (`0901-234 567`→`0901234567`, `15/01/1990`→`1990-01-15`); COMMIT `created=2`, batch→`committed`, 2 resident có mã unique; cleanup OK.

**Môi trường:** máy D: thiếu `vendor/laravel/octane` (composer install chưa chạy đủ) → artisan không boot. Đã `composer install --ignore-platform-reqs` (Windows thiếu ext pcntl/posix cho horizon/octane — chỉ dùng khi chạy queue/octane, không ảnh hưởng dev). Sau đó artisan/migrate/tinker chạy bình thường.

**Còn lại:** (a) UI wizard 6 bước cho BQL (upload→map→preview bảng validate→commit) nối nút "Nhập dữ liệu" đang stub ở `ResidentDirectory`; (b) file mẫu tải về (sinh từ `columns()` example); (c) i18n message validate (hiện English "id no required"). (d) áp profile cho HQ/SA khi cần.

---

## 2026-07-20 — Nền Import/Export dùng chung 3 tầng (Increment 1)

**Phạm vi:** dựng lớp nền import/export panel-agnostic (dùng chung SA/HQ/BQL), port pattern production từ x1web (Filament v5), độc lập gói Excel (x2bms dùng `spatie/simple-excel`, KHÔNG kéo `maatwebsite/excel`). Chưa đụng schema.

**File mới:**
- `app/Support/Import/RowNormalizers.php` — chuẩn hóa `string/email/phone/date` + `header()` (normalize whitespace) + `value($row,$expected,$aliases)` (guess-match cột như Filament `ImportColumn::guess`).
- `app/Support/Import/RowIssue.php` — DTO cảnh báo/lỗi theo dòng (`warning()/error()/toArray()`), khớp `import_batch_rows.validation_errors`.
- `app/Support/Import/ImportSummary.php` — bộ đếm processed/created/updated/skipped/warnings/errors + issues; `counters()` ánh xạ `import_batches`.
- `app/Support/Export/ExportsCsv.php` — trait `streamCsv(rows, headers, mapRow, filenameBase)`: CSV streaming + BOM UTF-8. Không tự audit/scope (caller giữ trách nhiệm scope theo context + audit → trait độc lập tầng, không giấu side-effect).

**Áp dụng đầu tiên (bằng chứng):** `ResidentDirectory::export()` (BQL `/admin`) refactor dùng `ExportsCsv` — bỏ `fputcsv` thủ công, giữ nguyên audit + filter scope building.

**Verify:** `php -l` sạch 5 file; autoload OK; chạy thật: `phone(' 0901-234 567 ')→0901234567`, `date('15/01/2024')→2024-01-15`, `email(' Test@X.VN ')→test@x.vn`, `value` match cột qua alias + header 2 space; trait áp đúng (`class_uses`), `RowIssue::isError`/`ImportSummary::validRows` đúng. (Không boot full app được ở local do config Octane production-only → test qua autoload thuần.)

**Còn lại (Increment 2):** import staging cư dân BQL cần mở rộng `import_batches` (`import_type` +`residents`, `row_type` +`resident`) + cân nhắc `building_id` nullable — là delta schema, làm ở bước sau. Tham chiếu: memory `x1web-reusable-filament-for-x2bms`.

---

## 2026-07-18 — Merge PR#3 vào main · Handoff dự án · Hướng dẫn deploy (server/domain/CI-CD)

**Git:** PR #3 (`feat/bql01-04-resident-detail-password-reset`) đã **merge vào `main`** (merge commit `f2876e4`), nhánh feature đã xóa. Toàn bộ phiên 07-18 nay ở main.

**Handoff dự án:** `docs/SESSION_HANDOFF_20260718.md` — nguồn chân lý khi chuyển máy: tổng quan, trạng thái git, setup máy mới, biến `.env`, kế hoạch domain `xbuilding.vn`, điểm khởi đầu Flutter (cần API Phase 0 trước), bản đồ tài liệu. Kèm gói audit `handoff/mobile_backend_audit_20260718/` (15 file).

**Hướng dẫn deploy:** `docs/DEPLOYMENT_GUIDE.md` (11 mục) — mô hình 1 app phục vụ 5 subdomain (sa/hq/bql/web/api.xbuilding.vn), cài server Ubuntu (PHP 8.4-fpm/Nginx/MySQL/Redis/Supervisor/Node/Certbot), DNS, các bước deploy, Nginx 1 server block, HTTPS, queue+scheduler, **CI/CD GitHub Actions** (push main → SSH deploy), checklist. **2 việc CODE cần làm trước khi chạy subdomain thật:** (1) `->domain()` trong `*PanelProvider` (đọc từ env, ảnh hưởng `APP_URL`→link reset MK); (2) chuyển ảnh CCCD/chân dung từ disk public sang **private** + signed URL (theo SECURITY audit).

**Lưu ý:** Filament thực tế là **v5** (composer `filament/filament 5.*`) — sửa ghi nhớ cũ "v4".

---

## 2026-07-18 — BQL-01-04 Chi tiết cư dân 360 · Reset mật khẩu đa kênh · Mail SMTP · chuẩn action UX

**Phạm vi:** hoàn thiện cụm cư dân màn 04 (chi tiết 360), luồng đặt lại mật khẩu (list+detail), gửi mail thật, và chốt chuẩn UX action.

**1. Màn Chi tiết cư dân 360 (BQL-01-04)** — dựng lại bản GIÀU theo format `ApartmentProfile`: title = tên cư dân ở topbar · breadcrumb + action ở header Filament · KPI strip 7 ô · 6 section-tab (Hồ sơ tổng quan · Căn hộ · Phương tiện & thẻ · Công nợ · Phản ánh · Nhật ký). Tab tổng quan: hồ sơ (avatar `avatar_url`) + thông tin cá nhân + căn hộ liên kết + snapshot phí/công nợ + thành viên hộ + gợi ý AI rule-based. File: `app/Filament/Pages/ResidentDetail.php` (thay stub cũ), `resources/views/filament/pages/resident-detail.blade.php`. Tái dùng partial `apartment-residents-table`/`apartment-assets`/`apartment-feedback`/`apartment-timeline`.

**2. Fix z-index popup + avatar list.** Bảng listing freeze cột → popup ActionGroup (Filament render TRONG ô sticky z-index:3, KHÔNG teleport dù có `.teleport`) bị ô sticky hàng dưới đè. Fix (CSS scoped `.x2-bql-page`): `tr:has(.fi-dropdown-panel[style*="display: block"]) td { z-index:25 }`. Thêm cột avatar (`ImageColumn avatar_url ->circular()`) + avatar trong mobile card. File: `theme.css`, `ResidentDirectory.php`, `resident-directory.blade.php`.

**3. Đặt lại mật khẩu cư dân (dùng nhiều)** — trait chung `app/Filament/Concerns/ResetsResidentPassword.php`, nút ở CẢ màn list (row action 🔑) + detail (header). Popup 4 phương thức: **mật khẩu tạm** (Str::password 10, cast hashed) · **OTP** (6 số, cache 10') · **gửi link** · **tạo link copy (Zalo)**. Sau khi tạo → mở modal kết quả (`replaceMountedAction('residentResetResult')`) có ô + nút Copy (Alpine clipboard). Yêu cầu cư dân đã có tài khoản liên kết. Token sinh qua **Password broker** chuẩn Laravel.
- **Trang tiêu thụ token (guest):** route `GET/POST /reset-password/{token}` (`password.reset`/`password.store`) + `ResidentPasswordResetController` (dùng `Password::reset`) + view tự chứa `resources/views/auth/reset-password.blade.php` (branded, không phụ thuộc Vite). File: `routes/web.php`. **Bẫy đã fix:** trước đó link 404 vì CHƯA có route — nay đã có, verify E2E: set mật khẩu mới → Hash::check PASS + token tự xóa sau dùng.

**4. Gửi email thật (SMTP).** `config/mail.php` thêm `'test_to' => env('MAIL_TEST_TO_ADDRESS')`: khi có → MỌI email nghiệp vụ route về địa chỉ test (tiện kiểm thử); production để trống. Trait có `deliverResidentMail()` (gửi qua `Mail::html`, try/catch) + template branded `otpEmailHtml`/`resetLinkEmailHtml`. `.env`: `MAIL_MAILER=log`→`smtp` (elasticemail smtp.elasticemail.com:2525). **Verify:** gửi thật tới `chtchinh@gmail.com` OK (không exception); log driver trước đó ghi đúng OTP vào `storage/logs/laravel.log`.

**5. Chuẩn ACTION UX (chốt owner).** Màn detail/list nhiều action → **tối đa ~3 nút chính** + gom còn lại vào `ActionGroup` "Thao tác khác" (icon ellipsis, `->button()`); hành động hủy diệt/nhạy cảm nằm trong dropdown. **Màu nút theo ý nghĩa:** gold=tạo mới · success=duyệt/mở khóa · danger=xóa/khóa · warning=bảo mật(reset MK/OTP) · gray=trung tính · primary=nhấn mạnh. Áp mẫu: reset MK=warning, mở khóa=success, khóa=danger. Ghi vào `docs/LISTING_PAGE_STANDARD.md §5b` + memory.
- **Breadcrumb:** mục click được (thẻ `<a>`) tô màu link `x2-primary`; mục hiện tại (`<span>`) giữ xám. CSS trong theme admin (chỉ /admin).

**6. Rà notification/log (cho feature sau).** Backend ĐÃ có `notifications` (`owner_level` = platform/tenant/project = **3 tầng SuperAdmin/Tenant/BQL**), `notification_channels`, `notification_delivery_logs` (notification_id·user_id·resident_id·channel·status·error·sent_at), `notification_audiences`. Handoff CHƯA có màn "nhật ký gửi đa kênh + retry theo 3 tầng" (chỉ có BQL-07 trung tâm gửi + HQ-05-08 nhắc nợ + các audit log). → Màn log + retry là MỚI (owner tự thiết kế). Xem `docs/COMMUNICATION_LOG_DESIGN_NOTE.md`.

**Verify tổng:** `php -l` sạch mọi file; `npm run build` OK; render 200 (`/admin/residents`, `/admin/residents/{id}/detail`, `/reset-password/{token}`); Livewire::test tab/action; browser thật: reset flow (temp/otp/link) + copy + trang reset E2E + SMTP thật + màu nút/breadcrumb + z-index popup.

**CHƯA COMMIT** (đang trên working tree, HEAD vẫn `3d34216`). Tài liệu: cập nhật `LISTING_PAGE_STANDARD.md`, tạo `PASSWORD_RESET_AND_MAIL.md`, `COMMUNICATION_LOG_DESIGN_NOTE.md`, bộ `docs/operations/` + `docs/user-guide/`.

---

## 2026-07-03 — DS-03 đủ 10 màn (Button/Action/Badge/Status) + vá tab tiêu đề DS-02

**Yêu cầu:** làm bộ 3 DS-03, bám sát thiết kế + nội dung từng màn nhiều nhất; trước đó tab "Phân cấp tiêu đề" DS-02 bị chê sơ sài (thiếu ví dụ minh hoạ trên màn thật).

**DS-03 (bộ 3):** `DesignSystemSet3` (`/sa/design-system/ds03`) — 1 nav menu, **10 tab đủ 10 màn**: Button Hierarchy (kiểu nút/icon/size + ví dụ ngữ cảnh + hướng dẫn + quy tắc thứ bậc) · Page Action Bar (là gì + thứ tự ưu tiên + 4 nhóm quy tắc + 3 ví dụ list/detail/tabbed) · Compact Action Group · Header Quick Create vs Page Create · Row Actions (nguyên tắc + bảng row-action + thứ tự + menu More) · Bulk Action Bar (bulk bar + bảng chọn + vị trí/trạng thái) · Split Button (overview + khi dùng + ví dụ phê duyệt/xuất/thanh toán/footer) · Badge Count (vị trí + biến thể + màu + KPI thật) · Status Pill (icon+màu+text pills + bảng + chi tiết + ngữ cảnh) · Action Decision Matrix (bảng 10 dòng + quy tắc nhanh). Ảnh DS-03 **lệch nhãn** (tên file ≠ nội dung) → map theo tiêu đề thật.

**Vá DS-02 tab "Phân cấp tiêu đề":** thêm card "Ứng dụng phân cấp tiêu đề trên màn thật" — mock màn cư dân có **đánh số ①–⑦** đúng vị trí Header/Page-tab/Section/Card/Form/Drawer/Modal title (bám ảnh DS-02-02).

**Verify:** `php -l` sạch; `view:cache` compile toàn bộ OK; render `/sa/design-system` + `/ds02` + `/ds03` = **200** (đủ marker 10 tab; pill icon dùng Blade::render chạy tốt); `npm run build` OK. Status pill = icon + màu + text (đúng rule DS-03).

---

## 2026-07-03 — DS-02 đủ 10 màn + restructure "mỗi bộ 1 menu, trang → tab" + spacing DS-02

**Yêu cầu:** margin/padding đúng DS-02; mỗi bộ handoff = 1 nav menu, các trang gộp vào tab; DS-02 làm ĐỦ 10 màn với đầy đủ nội dung. Guide ở /sa; chuẩn áp chung /sa /hq /admin (theme.css + component x2.*).

**Spacing DS-02:** `theme.css` token `--x2-card-radius 16` / `--x2-input-radius 12` / `--x2-section-gap 24` / `--x2-card-padding 20` + `--color-x2-info #06b6d4`. `x2.card.info` → radius rounded-2xl, padding px-5 py-4, title 15px. 6 partial bộ-1 → nhịp 24px (gap-6/mt-6).

**Restructure (1 bộ = 1 menu, trang → tab):** xoá 6 page class rời + `_nav`; strip wrapper 6 blade thành partial.
- **Bộ 1** `DesignSystemSet1` (`/sa/design-system`, HasForms) — 6 tab: Nền tảng · KPI & Bảng · Nút · Form & Lọc · Modal & AI · Tabs & Chi tiết.
- **Bộ 2** `DesignSystemSet2` (`/sa/design-system/ds02`) — **10 tab bám sát 10 màn DS-02**: Typography (thang chữ + ứng dụng) · Phân cấp tiêu đề (7 cấp + rule) · Token màu (Navy/Gold/Blue/Neutrals/Semantic + live preview) · Màu ngữ nghĩa (overview + banner + notice + bảng spec bg/text/border hex + debt/maintenance severity) · Icon (8 nhóm + preview panel) · Spacing (thang 4–48 + áp dụng) · Mật độ (Comfortable/Default/Compact) · Radius & Shadow (thang xs–xl + nơi dùng + elevation 0–5 rgba + radius component) · Accessibility (focus/hover/disabled/readonly/permission/empty + contrast AA/AAA + checklist) · Showcase (6 KPI token + tổng quan + màn Chất lượng dữ liệu thật).

**Verify:** `php -l` sạch; `view:cache` compile toàn bộ OK; render `/sa/design-system` + `/sa/design-system/ds02` = **200**; `npm run build` OK.

**Chốt token:** DS-02 xác nhận Plus Jakarta + Inter (Manrope trong Design/*.png cũ bỏ). Màu trong guide bám ảnh DS-02 (Navy 900 #0B1533…, Semantic Success #22C55E, Info #0EA5E9, AI #8B5CF6).

---

## 2026-07-03 — DS guide Forms: dùng component Filament THẬT (đối chiếu UI)

**Yêu cầu chủ dự án:** đối chiếu guide với bộ component Filament, nhất là dropdown/select & input — làm đúng nhất với UI.

**Vấn đề:** trang `DesignSystemForms` trước tự viết `<input>`/`<select>` thô bằng Tailwind → KHÔNG khớp UI Filament thật.

**Sửa:** `DesignSystemForms` giờ `implements HasForms` + `InteractsWithForms`, định nghĩa `form(Schema)` render **field Filament thật** trong Grid(3) × 3 Section: (1) TextInput (text/search prefixIcon/phone tel prefix +84/amount numeric prefix VND) + Textarea maxLength + FileUpload multiple + DatePicker native(false) range; (2) Select native(false) + Select multiple + CheckboxList + Radio + Toggle; (5-6) TextInput required + helperText (chỉ dẫn) + disabled + Placeholder. `mount()` fill mặc định. Blade chỉ còn `{{ $this->form }}` + Filter Bar (x2) + Drawer mock (pattern tổ hợp, không phải field đơn). Namespace v5: `Filament\Schemas\{Schema,Components\Section,Components\Grid}` + `Filament\Forms\Components\*` (copy từ ResidentForm).

**Verify:** `php -l` sạch; `view:cache` OK; render `/sa/design-system/forms` = **200** (form Filament resolve + render, dropdown/select/checkbox/radio/toggle/date/file là component thật); 6/6 route DS vẫn 200; `npm run build` OK.

**Còn:** các trang khác chủ yếu là component X2 tùy biến (buttons/card/table/badge — đúng DS của mình) + mock pattern (modal/drawer/notification/timeline) — hợp lệ. Dropdown/kebab ở trang Buttons vẫn là mock minh hoạ pattern ActionGroup.

---

## 2026-07-03 — Design System: menu + 6 trang hướng dẫn trên /sa (living style guide)

**Bối cảnh:** chủ dự án đưa 6 ảnh `handoff/0307/Design/*.png` (các trang tài liệu Design System) và yêu cầu tạo 1 menu trên /sa để làm trang hướng dẫn cho bộ này.

**Menu:** thêm nav group `Design System` (icon swatch) vào `SaPanelProvider`.

**6 page class** (`app/Filament/Sa/Pages/DesignSystem*.php`, trait `PlatformScreen` → chỉ SuperAdmin, slug `design-system[/…]`):
1. `DesignSystemFoundations` (`/sa/design-system`) — Typography (Plus Jakarta/Inter), Màu, Spacing, Bo góc, Điều hướng, Bố cục, Nguyên tắc.
2. `DesignSystemDataDisplay` (`/data-display`) — KPI (dogfood `x-x2.card.kpi`), loại card, bảng (`x-x2.table.data`), trạng thái bảng.
3. `DesignSystemButtons` (`/buttons`) — thứ bậc nút (`x-x2.btn`), split/group, topbar, dropdown/kebab, badges/status pills.
4. `DesignSystemForms` (`/forms`) — input, controls, filter bar (`x-x2.filter.bar/chip`), drawer lọc, validation states.
5. `DesignSystemOverlays` (`/overlays`) — modal/drawer, wizard, thông báo, approval, AI, system states.
6. `DesignSystemRecords` (`/records`) — kiểu tab, record detail, info blocks, related lists, timeline, AI side panel.

**Views:** `resources/views/filament/sa/ds/*.blade.php` + partial `_nav.blade.php` (pill sub-nav 6 trang). Dùng **token thật + component x2 thật** → guide vừa là tài liệu vừa là bản test component.

**Verify:** `php -l` 6 class sạch; `view:cache` compile toàn bộ blade OK; render headless 6 route `/sa/design-system*` = **200** (login platform admin x2bms@x2bms.vn); `npm run build` OK (theme 676KB, class x2-ai tint mới compiled). CHƯA screenshot pixel (preview cần chạy ở repo có deps).

**Lưu ý cần chốt:** 6 ảnh Design hiển thị **Manrope** + Navy `#0D1B2A`/Gold `#D4A017`; guide tôi dựng theo **giá trị ĐANG hiện thực** (DS-01: Plus Jakarta + Navy `#0B2146`/Gold `#D5A331`). Cần chủ dự án xác nhận bộ token canonical (DS-01 mới hay ảnh cũ) để đồng bộ.

---

## 2026-07-03 — DS-01 Phase 1 (đợt 1): bộ component list/dashboard + áp màn Danh sách cư dân (05)

**Component mới** (`resources/views/components/x2/`, dotted namespace, Blade thuần):
- `btn` (`x-x2.btn`): variant primary(blue)/gold(CTA)/outline/danger/ghost + size + icon @svg + loading/disabled state.
- `card/kpi` (`x-x2.card.kpi`): KPI DS-01 — icon tròn tint, số dùng `.font-title` (Plus Jakarta), trend ▲/▼ + "so với tháng trước", link "Xem chi tiết →", state loading (skeleton).
- `card/info` (`x-x2.card.info`): card có tiêu đề + slot `actions` + body.
- `page/tabs` (`x-x2.page.tabs`): **hàng tab trái + action page-level phải cùng hàng** (chữ ký DS-01), tab active gạch chân xanh + đậm, badge count; hỗ trợ `wire` (wire:click) hoặc `url`.
- `page/action-group` (`x-x2.page.action-group`): cụm action phải cho trang không tab.
- `filter/bar` + `filter/chip`: toolbar trên bảng (slot savedView/search/trailing + nút "Bộ lọc nâng cao" badge) + chip filter có nút xoá.
- `table/data` (`x-x2.table.data`): shell bảng bespoke (slot head/body/footer, state empty+loading skeleton, sticky, row ~56px) + `table/bulk-actions` (chỉ hiện khi có chọn, mobile → sticky đáy).
- Giữ nguyên component flat cũ (kpi-card/data-table/action-bar…) → trang đã build không vỡ.

**Áp màn Danh sách cư dân (DS-01-05)** — `ResidentDirectory` + blade:
- Bỏ `x-x2.action-bar subtitle=...` (subtitle bị cấm) → `x-x2.page.tabs` 5 tab (Tất cả/Chủ sở hữu/Người thuê/Chờ duyệt/Đã khóa) + count, action inline (Nhập/Xuất/+Thêm mới gold).
- Thêm `public $activeTab` + `setTab()` + `scopeByTab()`; tab wire vào query Filament (owner/tenant qua whereHas relations, pending/locked qua status).
- KPI nâng lên `x-x2.card.kpi` 5 thẻ (thêm "Cập nhật gần đây"); **KPI = tổng theo context, KHÔNG đổi theo tab/filter** (đúng rule DS-01). Table Filament giữ nguyên.

**Verify:** `php -l` sạch; `view:cache` compile toàn bộ blade OK; render headless `/admin/residents` = **200**, đủ marker (tab labels, `font-title`, "Thêm mới", "Cập nhật gần đây"). CHƯA screenshot pixel (preview thiếu deps — verify bằng render headless repo chính). Toolbar filter tùy biến + đối chiếu pixel để đợt sau.

**Tiếp:** component `record.*`/`approval.*`/`ai.*` khi làm màn 06/07/09; refactor tiếp các màn list khác.

---

## 2026-07-03 — DS-01 Phase 0: font Plus Jakarta Sans + design tokens (nền design-system)

**Bối cảnh:** khởi động track DS-01 (`docs/DS01_EXECUTION_PLAN.md`) — bộ Design System chính thức. Chủ dự án chốt: /admin·/hq·/sa bespoke đúng thiết kế, /fila giữ UI mặc định Filament, **font Plus Jakarta Sans áp cho tất cả panel**.

**File đổi:**
- `resources/css/filament/admin/theme.css`: `--font-title` Manrope → **'Plus Jakarta Sans'**; selector `.fi-header-heading/h1-4/.font-title` dùng PJS; palette chỉnh theo DS-01 tokens (`--color-x2-navy #0b2146` navy-900, `--color-x2-navy-950 #071a3a`, `--color-x2-gold #d5a331` gold-600, `--color-x2-ai #7c3aed`, canvas #f8fafc); thêm `:root` layout tokens (`--x2-sidebar-width 20rem`, `--x2-sidebar-collapsed-width 5rem`, `--x2-topbar-height 4.25rem`, `--x2-content-padding 1.5rem`, `--x2-card-radius 12px`, `--x2-button-height 40px`, `--x2-table-row-height 56px`).
- `AdminPanelProvider`/`HqPanelProvider`/`SaPanelProvider`: link bunny.net `manrope` → `plus-jakarta-sans:400,500,600,700,800`. /admin thêm `->sidebarWidth('20rem')->collapsedSidebarWidth('5rem')`.
- `FilaPanelProvider`: thêm `->font('Plus Jakarta Sans')` (giữ chrome mặc định, chỉ đổi typeface).
- `resources/views/filament/hooks/header-cluster.blade.php`: hardcode `font-family:'Manrope'` → 'Plus Jakarta Sans'.

**Verify:** `php -l` 4 provider sạch; không còn `manrope` trong code (chỉ docs). **CHƯA build/render** — worktree thiếu `node_modules`/`vendor`. Cần chạy ở repo có deps: `npm run build && php artisan optimize:clear`, rồi kiểm topbar/sidebar/KPI đổi sang Plus Jakarta + sidebar 20rem đối chiếu ảnh DS-01-01. Font-link (renderHook) hiệu lực ngay; thay đổi theme.css cần build.

**Tiếp theo:** Phase 1 — bộ component dotted namespace `x2.shell.*/nav.*/header.*/page.*/card.*/filter.*/table.*/record.*/approval.*/ai.*` (Blade, 13 state), giữ alias flat cũ.

---

## 2026-07-02 — BQL-03-02 Chu kỳ phí & đợt thu + drawer "Thiết lập kỳ phí" (dựng đúng UI)

**Migration `2026_07_02_000008_fee_cycles_bql0302`** (add-only): `billing_periods` += `name`, `fee_category`, `scope_label`, `expected_units`, `expected_amount`. Seed `seedBql0302Cycles`: 10 kỳ phí CP-YYYY-MM-XX (một kỳ/loại phí/tháng) khớp ảnh — **6 đang mở / 3 chờ chốt / 1 đã phát hành** (status open/pending_close/published). Tách khỏi 7 billing_periods theo tháng của backbone (03-02 lọc code LIKE 'CP-%').

**Page `FeeCycleList`** (`/admin/fees/cycles`, ẩn nav, vào từ pill "Chu kỳ phí" trên màn Khoản thu) + view: KPI 4 (đang mở 6/chờ chốt 3/đã phát hành 1/tổng 10), bảng kỳ phí (Mã/Tên/Loại phí/Phạm vi/Kỳ thu/Trạng thái) + bulk (Chốt kỳ/Phát hành) + **drawer "Thiết lập kỳ phí"** (Alpine slide-over): step indicator 5 bước, form trái (①Thông tin ②Phạm vi ③Nguồn dữ liệu&quy tắc ④Lịch chạy ⑤Xem trước 4 card), panel phải "Tóm tắt kỳ phí" + Hướng dẫn + "Kiểm tra trước khi tạo" checklist, footer Hủy/Lưu nháp/Chạy thử/Tạo kỳ phí. `createDraftCycle()` tạo kỳ nháp thật + notification. Thêm sub-nav pill trên FeeCatalog (Biểu phí/Chu kỳ phí).

**Fix path 2 máy:** `.claude/launch.json` `runtimeExecutable` đổi `C:\Users\ADMIN\...php.bat` → **`php`** (portable, mỗi máy PATH tự trỏ Herd). Preview MCP chạy được.

**Verify:** `php -l` sạch; `migrate:fresh --seed` sạch (000008 OK, ~49s); `npm run build` (Node 22) OK; `_render_admin.php "fees/cycles,fees/catalog"` → 200/200; **preview thật:** đăng nhập x2bms → `/admin/fees/cycles` render đúng (KPI 6/3/1/10, rows CP-*), mở drawer "Thiết lập kỳ phí" khớp ảnh (step indicator, §1-§2 field đúng, Số căn 1.248, footer 4 nút). Screenshot xác nhận.

**Lưu ý side-effect:** backbone 1.248 căn làm dashboard WEB-01-01 đổi số (Tỷ lệ thu 95.9%, Đã thu 3,21 tỷ, Công nợ đến hạn 2.220 tr) — dữ liệu thật nhưng lệch ảnh gốc 96.2%/2.45 tỷ. Cần chốt với chủ dự án: giữ (ưu tiên BQL-03) hay cô lập số dashboard.

**Slice 3 đã xong:** 03-01, 03-02, 03-04, 03-05, 03-06, 03-09 (6/10). **Còn:** 03-03, 03-07, 03-08, 03-10.

---

## 2026-07-02 — BQL-03-04 Bảng kê phí cư dân + BQL-03-06 Sổ công nợ cư dân

**Chung:** thêm trait `App\Filament\Concerns\FinanceScope` (financeBuildingId = toà chính dự án, currentPeriod, money/moneyCompact) dùng cho các màn tài chính. Thêm relation `Apartment::residents()` (belongsToMany qua resident_apartment_relations) + `Apartment::statements()`. Cast `statements.viewed_at/due_date`. Seed backbone bổ sung: **dòng phí** (statement_lines 5-7 dòng/bảng kê mới, exact-sum → cột "Số khoản phí" + chi tiết 03-09) và **lịch sử kỳ trước** cho 24 debtor (6 kỳ đã thanh toán → ledger 03-06).

**BQL-03-04** (`/admin/statements`, nav 'Hóa đơn & thanh toán'): Page `StatementList` (WithPagination) + view. 5 KPI khớp ảnh: **Chờ phát hành 124 · Đã phát hành 1.086 · Đã xem 732 · Quá hạn 148 · Tổng phải thu 8,42 tỷ**. Bảng 11 cột (Mã/Căn hộ+Cư dân/Kỳ/Số khoản phí/Phải thu/Đã TT/Còn nợ/Ngày PH/Hạn TT/Trạng thái/Thao tác), phân trang thật 10 dòng, sort hash-shuffle để mỗi trang mix trạng thái. Ẩn `StatementApprovalQueue` cũ khỏi nav (giữ 4 mục tài chính đúng ảnh).

**BQL-03-06** (`/admin/debts/{record}`, ẩn nav, link từ 03-05): Page `DebtLedger` + view. Header cư dân + 4 KPI (Nợ hiện tại=bucket 0-30 / Nợ quá hạn=31-90+ / Số kỳ còn nợ / Tổng đã TT năm nay) + bảng công nợ theo kỳ (phát sinh/đã thu/còn nợ/trạng thái + tổng) + biểu đồ tuổi nợ (4 bucket) + thao tác nhanh.

**Verify:** `php -l` sạch; `migrate:fresh --seed` sạch (~46s); `_render_admin.php "debts,debts/1,statements,fees/catalog"` → **200×4**; grep HTML: 03-04 KPI 1.086/124/732/148/8,42 tỷ đúng, trang 1 mix trạng thái (đã PH/đã TT/chờ PH/quá hạn).

**BQL-03-09** (`/admin/statements/{record}`, ẩn nav, link từ 03-04): Page `StatementDetail` + view. Cột trái (căn hộ+cư dân / kỳ phí / trạng thái TT) + giữa (chi tiết dòng phí từ statement_lines + Tổng trước VAT/VAT 8%/Tổng cộng + timeline phát hành-xem-thanh toán-hạn) + phải (thao tác Phát hành/Điều chỉnh/Gửi lại/In PDF + checklist). Render `/admin/statements/13` → 200.

**Đã xong Slice 3:** 03-01, 03-04, 03-05, 03-06, 03-09 (+backbone). **Còn:** 03-02 Chu kỳ phí+wizard · 03-03 Chi tiết kỳ phí · 03-07 Duyệt điều chỉnh (cần seed adjustment) · 03-08 Nhắc nợ/chiến dịch (cần seed campaign cho tenant demo) · 03-10 Nhật ký thao tác (cần seed audit tài chính).

---

## 2026-07-02 — BQL-03 backbone dữ liệu (1.248 căn, số khớp ảnh 100% thật) + BQL-03-05 Công nợ & tuổi nợ

**Quyết định chủ dự án:** dữ liệu tài chính seed theo hướng **"phình lên ~1.248 căn, mọi số khớp ảnh, 100% bản ghi thật"** (không dùng snapshot). Vì 6 màn (03-02..09) phụ thuộc, dựng **nền seed hợp nhất trước**, verify từng con số, rồi mới dựng UI.

**Migration `2026_07_02_000007_bql03_receivables_columns`** (add-only): `statements` += `viewed_at`, `due_date`, `assignee_name`, `sent_channel`; `debts` += `code`, `resident_name`, `last_period_code`, `bucket_0_30/31_60/61_90/over_90`, `risk_level`, `recovery_status`, `assignee_name`.

**Seed `seedBql03Receivables`** (gọi trong run() sau seedBillingAndPayments; bulk-insert):
- Scale Tòa A (SG-A) lên **1.248 căn** (+1.128 căn code A/B/C dạng `A12.06`, mỗi căn 1 cư dân `CDX-*` + relation owner).
- **Bảng kê kỳ T7/2026:** tổng 1.210 = **published 1.086 / pending 124**; trong published: **viewed 732 / overdue 148**; **tổng phải thu = 8.420.000.000 (8,42 tỷ)** (phân bổ exact-sum). Mỗi bảng kê có mã `BK-2026-07-####`, kênh gửi, người phụ trách.
- **Sổ công nợ:** **24 dòng** (mã `AR-2026-####`), aging **1,02 tỷ / 650tr / 320tr / 210tr** (tổng 2,20 tỷ — lưu ý ảnh ghi "2,18 tỷ" là số lệch trong ảnh; ta hiển thị đúng tổng buckets). Risk theo thứ hạng nợ quá hạn: **critical 4 / high 6 / medium 8 / low 6**; recovery_status + assignee.
- Helper `distribute()` chia tổng thành N phần exact-sum (không âm).

**Scope tài chính:** các màn 03 scope theo **toà chính của dự án (SG-A)** qua `financeBuildingId()` (= building nhỏ nhất trong project) — khớp topbar 1 toà; toà phụ SG-B (6 bảng kê/4 nợ) không lẫn vào KPI.

**BQL-03-05 as-built** (`/admin/debts`, nav 'Tài chính – Phí' > 'Công nợ'): Page `DebtAgingList` + view: 5 KPI aging + bảng 12 cột (Mã/Căn hộ+Cư dân/Kỳ gần nhất/Tổng nợ/4 bucket/Mức rủi ro/Người phụ trách/Trạng thái thu hồi/Thao tác) + filter row + bulk bar (Gửi nhắc nợ/Giao xử lý/Đề nghị khóa tiện ích/Xuất). Helper `money()`/`compact()` format VND (tỷ/triệu). Đọc DB thật, scope SG-A.

**Verify:** `php -l` sạch; `migrate:fresh --seed` sạch (000007 OK, ~43s); `_chk.php` (bootstrap script) xác nhận **apartments=1248, published=1086, pending=124, viewed=732, overdue=148, receivable=8.42 tỷ, debts=24, buckets 1,02/0,65/0,32/0,21 tỷ, không âm, risk 4/6/8/6**; `_render_admin.php "debts,fees/catalog"` → **200/200**; grep HTML render `/admin/debts`: 24 mã AR-2026, aging tỷ/triệu, badge rủi ro/thu hồi hiện đúng.

**Tiếp (cùng data đã sẵn):** 03-02 Chu kỳ phí+wizard · 03-03 Chi tiết kỳ phí · 03-04 Bảng kê phí cư dân (KPI 124/1.086/732/148/8,42 tỷ) · 03-06 Sổ công nợ cư dân · 03-07 Duyệt điều chỉnh · 03-08 Nhắc nợ/chiến dịch · 03-09 Chi tiết bảng kê · 03-10 Nhật ký thao tác.

---

## 2026-07-02 — CHỐT: 3 tầng scope của hệ thống (ghi lại cho rõ ràng)

Hệ thống có **3 phạm vi (scope) lớn**, ánh xạ theo `tenant_id` / `project_id`:

| Panel | Tầng | Scope dữ liệu | Ai vào |
|---|---|---|---|
| **`/sa`** | SuperAdmin / Nền tảng | **Toàn nền tảng** — xuyên mọi tenant (không giới hạn tenant_id) | Platform admin |
| **`/hq`** | Công ty quản lý toà nhà | **`tenant_id`** — 1 công ty, **đa dự án** (CurrentContext::hqProjectIds / hqAllProjectsSelected, session `hq_tenant_id`); platform admin có thể "as a company" | Tenant operator (company_admin) + platform admin |
| **`/admin`** | Ban Quản lý (BQL) vận hành | **`tenant_id` + `project_id`** — vận hành **MỘT dự án** (CurrentContext::projectId, workspace `bql`); `building_id` chỉ là filter | BQL staff + cấp trên |

- Nói gọn: **/sa = platform · /hq = tenant_id (đa dự án) · /admin = tenant_id + project_id (một dự án)**.
- Nhiều bảng nghiệp vụ scope theo `building_id` (BelongsToProject suy ra project qua building) — ví dụ statements/debts/billing_periods không có cột project_id riêng, scope bằng building_id thuộc dự án.
- `$scope` trong seeder = `['tenant_id', 'building_id']`. Global scope `BelongsToTenant` + `CurrentContext` áp scope theo tầng đang đăng nhập.

---

## 2026-07-02 — Slice 3 (BQL-03 Tài chính) bắt đầu: BQL-03-01 Biểu phí & quy tắc tính phí

**Bối cảnh:** Bắt đầu Slice 3 theo `WEB_BQL_EXECUTION_PLAN.md`. Handoff bộ riêng BQL-03 ở **`C:\app\x2-bms\handoff\WEB-BQL-03_FEE_CYCLE_STATEMENT_DEBT_BILLING_HANDOFF_20260702\`** (máy này; docs cũ trỏ `D:\` là máy kia). Đã đọc trọn contract + xem 10 ảnh. **Tên file ảnh lệch nội dung** → bám tiêu đề trên ảnh: 03-01=Biểu phí (không phải fee cycle như tên file), 03-02=Chu kỳ phí+wizard, 03-09=Chi tiết bảng kê (không phải report), 03-10=Nhật ký thao tác.

**Map handoff→model có sẵn:** fee_categories→FeeType · fee_rules/versions→FeeRate/FeeFormula(+Version) · fee_cycles→BillingPeriod · fee_cycle_runs(+items)→BillingRun(+Item) · statements(+lines)→Statement(+Line/Approval/PublishLog) · debt_ledgers→Debt · debt_adjustments→BillingAdjustment · collection_campaigns(+msgs)→DebtReminderCampaign(+Log). Reuse tối đa, không dựng lại schema.

**BQL-03-01 as-built** (`/admin/fees/catalog`, nav 'Tài chính – Phí' > 'Khoản thu'):
- Migration `2026_07_02_000006_extend_fee_catalog_bql03` — thêm cột hiển thị cho `fee_types`: `applies_to`, `frequency`, `vat_percent`, `formula_text`, `effective_from`, `is_complex` (add-only). FeeType casts + $guarded=[].
- Page `App\Filament\Pages\FeeCatalog` + view `fee-catalog.blade.php`: 5 KPI (đang áp dụng/sắp hiệu lực/tạm ngưng/công thức phức tạp/cập nhật tháng này), bảng catalogue 10 cột (Mã/Tên/Nhóm/Đối tượng/Công thức/Chu kỳ/VAT/Hiệu lực/Trạng thái/Thao tác) + filter row + "Quy tắc tính nổi bật" 5 card. Đọc FeeType tenant-scoped, không hardcode.
- Seed `seedFeeCatalog` + `seedBql03CatalogExtra`: enrich 5 fee type chức năng (giữ code QL/RAC… cho billing) + sinh 33 catalogue rows BF-* để **KPI khớp ảnh chính xác: 28 active / 6 pending / 4 inactive / 9 complex / 12 cập nhật tháng này** (backdate updated_at 26 dòng còn lại).
- Trạng thái dùng string 'active'/'pending'/'inactive' (fee_types.status là string, mở rộng thêm 'pending').

**Công cụ verify mới:** `_render_admin.php` (gitignore) — login user, render `/admin/<slug>` in HTTP status (mặc định platform admin). `_chk.php` để đếm nhanh qua DB.

**Verify:** `php -l` sạch 4 file; `migrate:fresh --seed` sạch (000006 OK, seed ~44s); đếm fee_types = 38 (28/6/4/9/12 khớp ảnh); `_render_admin.php "fees/catalog,my-work,access"` → **200/200/200**.

**Tiếp:** 03-02 Chu kỳ phí & wizard (BillingPeriod), 03-03 Chi tiết kỳ phí (BillingRun) — cùng nhóm 'Khoản thu'; rồi 03-04..03-10.

---

## 2026-07-02 — Tách 3 panel + shell + Web BQL Slice 0/1/2 + mobile/search/profile/context-switcher

Phiên dài, nhiều hạng mục (tất cả trên `/admin` = workspace BQL trừ khi ghi khác). Chưa commit.

**1. Tách 3 panel theo 3 tầng.** Tạo `SaPanelProvider` (`/sa`, `EnsurePlatformAdmin`); chuyển **35 page platform** từ `app/Filament/Pages` → `app/Filament/Sa/Pages` (SaaS Billing/Integration/Support/Nền tảng+WEB-UX-22), **4 page AI** → `app/Filament/Hq/Pages`. `/admin` còn **13 page thuần BQL**. Sửa ~5 tham chiếu blade AI-class, rewire workspace switch redirect (bql→/admin, hq→/hq, superadmin→/sa). 3 panel boot sạch (route:list).

**2. Shell dùng chung (quyết định chủ dự án).** Tiêu đề lên header (`topbar-start`), **search căn giữa** (flex-1 gap), **bỏ subtitle** (drop trong `x-x2.action-bar`), **giữ số cột KPI theo thiết kế** — thêm `<x-x2.kpi-row :cols>` (6 card/hàng không tự co về 2/3).

**3. Slice 0 — BQL-00 Foundation (4 màn):** MyWork (`/admin/my-work`, inbox đa nguồn ApprovalRequest/Statement/ResidentApproval/PaymentRequest/WorkOrder/Feedback/Sla/Ioc/Audit + duyệt/từ chối ghi audit), AuditLogViewer (`audit-logs`), PermissionState (`access-denied`), ProjectSettingsPreview (`project-settings`). Thêm quan hệ `user()/building()` cho AuditLog, `creator()` cho WorkOrder.

**4. Slice 1 — BQL-01 (5 màn):** ApartmentTree (`apartments/tree`), HouseholdRelationships (`households`), MoveInOutHistory (`move-history`), ResidentTimeline (`resident-timeline`), ResidentDataQuality (`residents/data-quality`).

**5. Slice 2 — BQL-02 Access (5 màn, nav group mới "An ninh & Kiểm soát"):** AccessControlDashboard (`access`), VehicleRequests (`access/vehicle-requests`), AccessCards (`access/cards`), ResidentAccessProfile (`access/resident-profile`), AccountApprovalDetail (`residents/approvals/{id}`, link từ ResidentApprovalQueue).

**6. Đối chiếu ảnh handoff (quét 99 ảnh bằng 10 subagent).** Phát hiện `UI_IMAGE_INVENTORY.md` **sai tên↔nội dung ở batch 00–04** (01/02/04 xáo hoàn toàn, 03 lệch một phần; 05–09 chuẩn). Lập `UI_IMAGE_INVENTORY_CORRECTED.md` (bộ gốc) + verify bộ chủ dự án re-map `D:\Chinh\x2\handoff\01-04\` (`REMAP_VERIFICATION_20260702.md`, còn vài slot lệch). Memory: `x2bms-handoff-image-mislabels`.

**7. Mobile responsive header (WEB-UX-MOBILE).** `<x-x2.mobile-shell>` inject BODY_START (<lg), ẩn `.fi-topbar` mobile; hamburger dùng lại sidebar Filament làm drawer; header gọn + context row + bottom sheet. Bật cho **cả 3 panel**.

**8. Global search (WEB-UX-10).** `App\Livewire\GlobalSearch` — command palette dùng chung (desktop dropdown / mobile full), query Resident/Apartment/Feedback/WorkOrder scope context, recent + điều hướng nhanh + kết quả nhóm. Mở bằng nút search + Ctrl/K, render BODY_END 3 panel. (Filament global search dựa Resource không hợp panel Pages-only.)

**9. Profile (WEB-UX-02):** MyProfile (`my-profile`), SecuritySettings (`security`, đổi mật khẩu thật + 2FA + cảnh báo), LoginSessions (`sessions`, đọc bảng `sessions` thật, revoke). Nối avatar userMenuItems (trước `#`).

**10. Context switcher gộp 1 popup (WEB-UX-03).** `App\Livewire\ContextSwitcher` — Công ty→Dự án→Workspace/Vai trò, **gate quyền** (ẩn cột Công ty nếu không platform admin; ẩn workspace HQ/SA nếu không quyền; dự án chỉ cái được cấp). Thay 2 dropdown workspace+project bằng 1 chip header (admin+sa) + trigger mobile. Width **2/3 content** desktop / full mobile.

**Bẫy đã gặp:** (1) `transition()` là method reserved của Livewire — đừng đặt tên page method. (2) Nhiều cột model là **BackedEnum cast** (Vehicle/AccessCard/Resident status+type) → chuẩn hoá `enumVal()` trước khi dùng làm key/so sánh. (3) `@php use ... @endphp` bên trong `@auth` = fatal → dùng FQN. (4) **`theme.css` `@source` thiếu `resources/views/livewire`** → Tailwind không sinh class chỉ dùng ở component Livewire (z-[100], lg:pl-64, calc width) → modal lỗi vị trí; đã thêm source. (5) Tailwind arbitrary `w-[calc(...*2/3)]` vỡ (opacity) → dùng `*0.667`. (6) Bottom-sheet slide cần `x-transition:enter/enter-start/enter-end` tường minh.

**Verify:** build + `optimize:clear` sạch; route:list 3 panel OK (/admin 22 page BQL + profile/access); browser (preview) verify: dashboard/my-work/audit/project-settings/tree/households/move/timeline/data-quality/vehicle-requests/access-cards/access-dashboard/resident-profile/account-approval + mobile shell (drawer/search/bottom-sheet) + global search (kết quả thật "Nguyễn") + context switcher (3 cột platform admin, 2/3 width, mobile full) + profile pages. Memory cập nhật: `x2bms-web-admin-architecture`, `x2bms-build-roadmap`, `x2bms-handoff-image-mislabels`.

**CÒN LẠI:** Slice 3+ (BQL-03 Tài chính → 09); nối avatar menu /hq /sa; HQ context-row đa-dự-án trong mobile-shell; global search kết quả wiring sâu hơn; guard EnsureProjectContext→/access-denied.

---

## 2026-07-02 — HQ-03 + HQ-04: Tài liệu/Biểu mẫu/AI KB + Phân quyền/Hỗ trợ (20 màn /hq) — HOÀN TẤT HQ PORTAL

**Phạm vi:** 2 batch cuối. HQ Portal đủ **50/50 màn** (Phase 0 + HQ-01/02/03/04/05).

**HQ-03** (migration `2026_07_02_000004`, 12 bảng): `document_libraries`, `documents`, `document_versions`, `sop_templates`, `checklist_templates`, `checklist_items`, `template_assignments`, `config_inheritance_rules`, `ai_knowledge_sources`, `ai_knowledge_sync_logs`, `ai_test_questions`, `ai_test_runs`. Tái sử dụng `dynamic_forms` (form builder) + `knowledge_*` (KB). 10 màn nav 'Biểu mẫu & Tri thức': KnowledgeHub (03-01), SharedDocuments (03-02, folder tree + tab loại), SharedForms (03-03), FormBuilder (03-04, Alpine kéo trường), SopChecklists (03-05), TemplateAssignments (03-06), InheritanceRules (03-07), KnowledgeBaseHq (03-08), AiKnowledgeSources (03-09), AiKnowledgeTest (03-10). Seed khớp ảnh: docs 1842/SOP 356, forms 218/156/38/24, hub AI index 1256 (778/226/151/101), 6 nguồn tri thức (SharePoint 128.4GB…).

**HQ-04** (migration `2026_07_02_000005`, 4 bảng): `permission_groups`, `permission_group_items`, `two_factor_settings`, `login_sessions`. Tái sử dụng spatie roles/permissions, `user_role_scopes`, `audit_logs`, `support_tickets`/`support_kb_articles` (Batch 10). 10 màn nav 'Hỗ trợ & Phân quyền': AccessSupportOverview (04-01), UserManagement (04-02), RoleManagement (04-03), PermissionGroupsPage (04-04), PermissionMatrix (04-05, ma trận module×vai trò×5 hành động), HqActivityLog (04-06), SupportTickets (04-07), TicketDetail (04-08, route-model-binding), SlaReport (04-09), SupportKnowledgeBase (04-10). Seed khớp ảnh: users 1248 (1062/96/60/30), roles 18, tickets 386 (132/146/68/24/16), CSAT 4.62, SLA 88.4%, 8 nhóm quyền, 8 ticket T-SSG + messages.

**Bẫy:** (1) `dynamic_forms.current_version` là INTEGER → parse 'v2.3'→2. (2) Heredoc inline dễ vỡ khi có ký tự đặc biệt → dùng file generator `_gen_hq3.sh`/`_gen_hq4.sh` (Write literal) rồi `bash`, xoá sau.

**Verify:** `migrate:fresh --seed` sạch (000004/000005 OK); **51/51 route `/hq` render HTTP 200** (login HQ operator) — toàn bộ HQ Portal không hồi quy.

**HQ PORTAL DONE:** Phase 0 + 5 batch × 10 màn = 50 màn. 5 migration HQ (000001–000005). Tiếp (tùy chọn): action ghi thật cho các form/wizard còn ở mức UI, Sanctum cho API, Playwright screenshot.

---

## 2026-07-02 — HQ-05: Báo cáo công nợ, tài chính, thu chi đa dự án (10 màn /hq)

**Phạm vi:** Trọn batch HQ-05. Dashboard/aggregate dùng `metric_snapshots` (đã có từ HQ-02) theo đúng khuyến nghị handoff (không tạo bảng report riêng từng màn) + delta nghiệp vụ mới + seed + 10 Page.

**DB delta** (`2026_07_02_000003_create_hq05_finance`, 8 bảng): `debt_reminder_campaigns`, `debt_reminder_logs`, `cash_funds`, `cash_transactions`, `expenses`, `report_schedules`, `report_export_jobs`, `ai_insights`. 8 model.

**Seed** (`seedHq05`) cho T-SSG — chủ yếu `metric_snapshots` (89 dòng): aging 5 nhóm (tổng **1.024 tỷ**, nợ xấu >90 ngày 213.91 tỷ 20.7%), per-project aging (5 dự án, 4065 căn), debt_by_fee (5 loại), collection_rate (6 kỳ + 5 dự án), finance_kpi, project_cashflow (7 dự án, doanh thu **28.62 tỷ**), cashflow_kpi, top_debtor (10 hồ sơ + debt_kpi 1236/8.2465 tỷ/268/156), reminder_kpi (12 chạy/128.456 gửi/12.68 tỷ cam kết), ai_risk_kpi (68/100, dự báo 28.45 tỷ, 63.2%), ai_forecast (3 tháng). + 6 chiến dịch nhắc nợ + logs, quỹ + thu chi + 3 đề nghị chi, 4 lịch báo cáo + 4 job xuất, 10 ai_insights (xếp hạng rủi ro Top-10).

**Pages** (`app/Filament/Hq/Pages`, nav 'Báo cáo'):
- 05-01 `FinanceOverview` `/hq/finance/overview` · 05-02 `DebtByProject` `/hq/debts/by-project` · 05-03 `DebtAging` `/hq/debts/aging` (KPI 5 nhóm + stacked bar + donut + bảng chi tiết) · 05-04 `TopDebtors` `/hq/debts/top-debtors` (KPI + bảng + panel chi tiết Alpine) · 05-05 `CollectionRate` `/hq/collection-rate` · 05-06 `DebtByFeeType` `/hq/finance/debt-by-fee` · 05-07 `Cashflow` `/hq/finance/cashflow` (KPI + bảng hiệu quả tài chính + đề nghị chi) · 05-08 `DebtReminders` `/hq/debt-reminders` (KPI + bảng chiến dịch) · 05-09 `ReportExports` `/hq/finance/reports` · 05-10 `FinanceAiRisk` `/hq/finance/ai-risk` (KPI + dự báo + Top-10 rủi ro AI).

**Verify:** `php -l` sạch; `migrate:fresh --seed` sạch (000003 OK, seed ~36s); đếm seed đúng ảnh (aging 1024 tỷ, top-debtor 1236/8.25 tỷ/268, 6 campaign, 10 insight, 89 snapshot); **10/10 route render HTTP 200** (login HQ operator); HQ-01/02 không hồi quy.

**Tiếp:** HQ-03 (Biểu mẫu/tài liệu/AI KB — reuse document_templates/form builder/knowledge_*) hoặc HQ-04 (Phân quyền/hỗ trợ — reuse spatie/user_role_scopes/support_* Batch10).

---

## 2026-07-02 — HQ-02: Billing, ví công ty & tương tác Platform (10 màn /hq)

**Phạm vi:** Trọn batch HQ-02. Tái sử dụng Batch 07 (billing_invoices/lines, billing_payments, usage_records/periods, quota_alerts, billing_adjustments, billing_reconciliations, pass_through_*) + delta mới + seed cho tenant Sunshine Group + 10 Page.

**DB delta** (`2026_07_02_000002_create_hq02_billing`, 7 bảng): `wallets` (ví prepaid cấp công ty — khác pass-through theo kênh), `wallet_transactions` (sổ cái: top_up/deduct/allocation/refund/adjustment), `wallet_topup_requests`, `billing_rate_cards` (đơn giá/markup theo kênh), `plan_change_requests` + `plan_change_request_items`, `metric_snapshots` (read-model dashboard/dự báo). 7 model.

**Seed** (`seedHq02`): cho tenant T-SSG-HQ — ví (số dư **352.680.000** / hạn mức **1.000.000.000**, auto-topup 200M, Vietcombank ****8888), 12 wallet_tx (nạp **6 lần = 745.000.000**) + 4 phân bổ dự án (210/160/120/80 = 570M), 2 topup request; usage_records (SMS 174k/300k, Zalo 92k/120k, Email 78k/150k) + quota alert Zalo 76.7%; 5 rate card; metric_snapshots (cơ cấu chi phí **128.45M** = phí nền tảng 80.75M + pass-through 47.7M; xu hướng 6 tháng 96.8→128.45; top 4 dự án; dự báo T8 +6.3%); 6 hóa đơn platform + lines + payments; 2 reconciliation (matched/mismatch) + 1 adjustment; **128 plan_change_requests** (processing 18 / pending 27 / completed 78 / rejected 5).

**Pages** (`app/Filament/Hq/Pages`, nav 'Billing & Gói dịch vụ'):
- 02-01 `SaasCostOverview` `/hq/billing/overview` (KPI + xu hướng bar + donut cơ cấu + top dự án + hạn mức).
- 02-02 `BillingByProject` `/hq/billing/by-project`.
- 02-03 `CompanyWallet` `/hq/billing/wallet` (số dư/hạn mức + biểu đồ + phân bổ dự án + actions).
- 02-04 `WalletHistory` `/hq/billing/wallet-history` (filter loại GD).
- 02-05 `UsageMetering` `/hq/billing/usage`.
- 02-06 `PassThrough` `/hq/billing/pass-through`.
- 02-07 `PlatformInvoices` `/hq/billing/invoices`.
- 02-08 `BillingReconciliation` `/hq/billing/reconciliation`.
- 02-09 `CostForecast` `/hq/billing/forecast`.
- 02-10 `PlanChangeRequests` `/hq/billing/plan-changes` (KPI 128/18/27/78 + tab loại + search).

**Bẫy đã trả giá:** (1) Page class `BillingReconciliation` trùng tên model import ⇒ "Cannot redeclare class" → alias `BillingReconciliation as BillingReconciliationModel`. (2) Div-by-zero ở forecast khi tenant rỗng data → `max(array_merge([1], ...))`. (3) Render headless nên đăng nhập **HQ operator** `hq@sunshinegroup.vn` (có tenant_id) thay vì platform admin (chưa chọn công ty ⇒ tenant context sai) → `_render_hq.php` mặc định user này (arg 2 để override).

**Verify:** `php -l` sạch; `migrate:fresh --seed` sạch (000002 OK, seed ~36s); đếm seed đúng ảnh (ví 352.68M, topup 6×745M, plan-change 128=18/27/78/5, chi phí 128.45M); **11/11 route render HTTP 200** (overview + 10 màn HQ-02) qua `_render_hq.php`; HQ-01 không hồi quy.

**Tiếp:** HQ-05 (tài chính công nợ đa dự án) hoặc HQ-03 (docs/forms/AI KB), HQ-04 (IAM/support).

---

## 2026-07-02 — HQ-01: Danh mục dự án, BQL, nhân sự & gói dịch vụ (10 màn /hq)

**Phạm vi:** Trọn batch HQ-01 (10 screen) trên panel `/hq`. DB delta + models + seed khớp ảnh + 10 Page bespoke.

**DB delta** (`2026_07_02_000001_create_hq01_project_org`, ADD-ONLY, 7 bảng): `bql_teams`, `employee_project_assignments`, `employee_assignment_histories`, `project_subscription_periods`, `project_module_overrides`, `import_batches`, `import_batch_rows`. Tái sử dụng projects/staff_profiles(≈employees)/departments/plans/modules. Models tương ứng (BelongsToTenant + SoftDeletes; histories/rows là log/child → không soft delete). `employee_id` → `staff_profiles`.

**Seed** (`DemoDataSeeder::seedHq01`): tenant "Sunshine Group" (T-SSG-HQ) + HQ operator `hq@sunshinegroup.vn` (company_admin, scope tenant). 24 dự án khớp ảnh HQ-01-01 (Tổng 24 · active 18 · trial 3 · suspended 3 · gia hạn≤30d **6** · BQL thiếu **4**; donut Đầy đủ 8/Phổ biến 7/Thông minh 3/Trial 3/Tạm ngừng 3; Tòa nhà **32** · Căn hộ **12.540** · Diện tích **238.500**). 128 nhân sự khớp HQ-01-05 (đang làm 112 / chờ 16; phòng ban Ban giám đốc 18/Kỹ thuật 58/Kế toán 12/CSKH 22/Bảo vệ 18; **Đa dự án 36**). + bql_teams(24), assignments(148), histories(6), module overrides(8), import batch(1)+rows(8).

**Pages** (`app/Filament/Hq/Pages/*` + view `resources/views/filament/hq/pages/*`):
- HQ-01-01 `ProjectDirectory` `/hq/projects` (KPI + tab/search reactive + bảng + donut + tổng quan nhanh).
- HQ-01-02 `ProjectCreate` `/hq/projects/create` (wizard 5 bước Alpine + tóm tắt live + **save thật**: project+period+bql_team+audit).
- HQ-01-03 `ProjectDetail` `/hq/projects/{project}` (header+lifecycle+info+BQL+tab nhân sự+KPI/gói/module) — route-model-binding.
- HQ-01-04 `BqlSetup` `/hq/projects/{project}/bql` (định biên phòng ban + bảng BQL + liên hệ).
- HQ-01-05 `EmployeeDirectory` `/hq/employees` (KPI + tab phòng ban + donut + dự án thiếu nhân sự).
- HQ-01-06 `ProjectAssignment` `/hq/project-assignments` (chọn dự án + nhân sự khả dụng + **assign() thật**).
- HQ-01-07 `AssignmentHistory` `/hq/assignment-histories`.
- HQ-01-08 `ProjectPackage` `/hq/projects/{project}/package` (thẻ gói + ma trận tính năng + cấu hình).
- HQ-01-09 `ProjectModules` `/hq/projects/{project}/modules` (metrics + bảng entitlement).
- HQ-01-10 `ProjectEmployeeImport` `/hq/imports/projects-employees` (wizard + preview + file info).

**Bẫy đã trả giá:** (1) Record sub-page slug `{project}` vẫn đăng ký nav ⇒ Filament dựng link thiếu param ⇒ 500 mọi màn. Fix: override `shouldRegisterNavigation(): bool { return false; }` (property bị method của trait `HqScreen` ghi đè). (2) Filament route-model-binding tự resolve `{project}` → `Project`; `mount()` phải nhận `Project $project` (không `int`). (3) Guard tenant phải bypass cho platform admin (xem mọi dự án).

**Verify:** `php -l` sạch; `migrate:fresh --seed` sạch (000001 OK, seed ~35s); đếm seed đúng ảnh (script tạm); **11/11 route render HTTP 200** (overview + 10 màn HQ-01) qua `_render_hq.php` (platform admin).

**Tiếp:** HQ-02 (Billing/ví/platform — reuse Batch 07) hoặc HQ-05 (tài chính đa dự án).

---

## 2026-07-02 — HQ Portal Phase 0: hạ tầng panel /hq (Cổng Công ty / Tenant HQ)

**Phạm vi:** Dựng tầng GIỮA của mô hình 3 tầng (Platform → **HQ** → BQL). Panel `/hq` riêng cho công ty vận hành đa dự án, theo handoff `X2_BMS_HQ_FULL_CLAUDE_CODE_HANDOFF_20260702`.

**Files mới:**
- `app/Providers/Filament/HqPanelProvider.php` — panel id/path `hq`, theme navy/gold dùng lại `admin/theme.css`, 7 nav group (Tổng quan/Quản lý dự án/Nhân sự & BQL/Billing & Gói dịch vụ/Biểu mẫu & Tri thức/Hỗ trợ & Phân quyền/Báo cáo), discover `App\Filament\Hq\{Pages,Widgets}`, X2AI fab, shell hooks.
- `app/Http/Middleware/EnsureHqAccess.php` — chặn panel: chỉ `isPlatformAdmin()` hoặc `isTenantOperator()` (403 với BQL/cư dân).
- `app/Filament/Concerns/HqScreen.php` — gate màn HQ (platform admin | tenant operator; optional feature-gate qua `FeatureGateService`, không hardcode gói).
- `resources/views/filament/hq/brand.blade.php` (label "HQ Portal"), `header-cluster.blade.php` (company selector cho platform admin + multi-project scope dropdown + notif/help).
- `app/Filament/Hq/Pages/HqOverview.php` + `resources/views/filament/hq/pages/hq-overview.blade.php` — landing "Tổng quan HQ", KPI tính từ DB theo tenant + tập project đang chọn.

**Files sửa:**
- `app/Support/Context/CurrentContext.php` — thêm HQ multi-project: `hqProjectIds()` (∅ = tất cả), `hqAllProjectsSelected()`, `setHqProjects()`; `tenantId()`/`availableProjects()` honor `session('hq_tenant_id')` để platform admin thao tác "as a company".
- `routes/web.php` — `POST /context/hq-projects` (đặt phạm vi đa dự án), `GET /context/hq-tenant/{tenant}` (platform admin đổi công ty).
- `bootstrap/providers.php` — đăng ký `HqPanelProvider`.

**Verify:** `php -l` sạch; `optimize:clear` OK; render headless `/hq/overview` → **HTTP 200** (68KB) với platform admin (script tạm `_render_hq.php`, đã gitignore).

**Tiếp:** HQ-01 — DB delta (bql_teams, employee_project_assignments, employee_assignment_histories, project_subscription_periods, project_module_overrides, import_batches, import_batch_rows) + models + seed khớp ảnh + 10 màn.

---

## 2026-07-02 — Fix migrate:fresh FAIL: getTableListing() trả bảng của MỌI database trên server

**Phạm vi:** Migration soft-delete `2026_07_01_000025` FAIL trên máy dev có nhiều DB dùng chung MySQL server.

**Triệu chứng:** `SQLSTATE[42S02] Table 'x2bms._action_logs' doesn't exist` khi `alter table _action_logs add deleted_at`.

**Nguyên nhân gốc:** `Schema::getTableListing()` (Laravel 13/MySQL) trả về schema-qualified names của **TẤT CẢ database** trên server (`appsale._action_logs`, `tuart.app_log_action`, `x1.b_o_transactions`…), không chỉ `x2bms`. Fix cũ (strip dấu `.` cuối) biến `appsale._action_logs` → `_action_logs` rồi cố ALTER trong `x2bms` → không tồn tại → FAIL (bảng index 0 nên vỡ ngay đầu).

**Fix:** `includeTables()` trong migration 000025 — lấy `DB::connection()->getDatabaseName()`, **bỏ qua** bảng có prefix schema khác DB hiện tại; chỉ strip `db.` khi schema == database hiện tại.

**File:** `database/migrations/2026_07_01_000025_add_soft_deletes_and_archive.php` (chỉ sửa method `includeTables()`).

**Verify:** `php -l` OK; `migrate:fresh --seed` sạch — 000025 chạy 10s DONE, 000026/000027 DONE, seed 11.4s DONE.

---

## 2026-07-01 — Batch 10: Support Center, Ticket & Data Correction (10 màn, platform-level)

**Phạm vi:** Trọn gói Batch 10 (WEB-UX-30): 27 bảng, 27 model, 11 resource `/fila`, 8 màn bespoke `/admin` (phủ 10 screen), API `platform/support` + 10 test. Reconcile drop `support_tickets`/`support_ticket_comments`/`data_fix_requests` cũ.

**Yêu cầu UI của chủ dự án (đã áp):**
1. **Số thống kê đúng ảnh** — dashboard priority Critical 12 / High 46 / Medium 132 / Low 120 (tổng 310, phân bố ticket có kiểm soát) + 28 escalated + 37 near-breach; % (SLA 88.4 / breach 11.6 / CSAT 4.6) đọc từ `support_reports` snapshot `DASH-CURRENT`; report tháng 06 (1248 / 14h36m / 96.8% / 312 / 24 / 4.7) đọc từ `support_reports` type=resolution. Đều từ DB, không hardcode view.
2. **Listing luôn có title click → chi tiết** — mọi màn listing đặt `->action($this->detailAction())` trên cột tiêu đề (mở modal chi tiết).
3. **Textarea → HTML editor mặc định** — mọi field mô tả/lý do/nội dung/rollback trong form thêm mới dùng `RichEditor` (cả /admin lẫn /fila qua codemod).

**Files chính:**
- Migration `..._000027_create_support_center_batch10.php` (27 bảng + drop bảng cũ + archive clone audit/status_log/sla_event; FK ngắn `dcar_request_fk` tránh vượt 64 ký tự).
- 27 model `app/Models/Support*.php` + `Data*.php` + `TenantSupport*.php`; rewrite `SupportTicket` (bỏ BelongsToTenant), xoá `SupportTicketComment`/`DataFixRequest` cũ.
- Trait `app/Filament/Concerns/WritesSupportAudit.php` (ghi `support_audit_logs`).
- Seed `DemoDataSeeder::seedBatch10Support` (SLA policies, 4 team = 29 member, 2 tenant profile + contacts + entitlements, 310 ticket + 4 named có timeline, 2 escalation, 3 DCR + snapshot/diff/approval, 4 KB article, 2 report snapshot).
- 11 resource `/fila` (nav group 'Support Center', RichEditor, soft-delete UX).
- 8 Page bespoke `/admin` (nav 'Support Center', PlatformScreen): `SupportDashboard` (30-01), `SupportTicketQueue` (30-02/03/04 — queue + detail modal timeline + create + bulk assign + escalate/close/reopen + reply), `TenantSupportProfile` (30-05), `DataCorrectionRequests` (30-06 — approve 2 người cho high/critical), `ControlledDataFixWizard` (30-07 — snapshot→execute [gate bắt buộc snapshot]→rollback), `SupportKnowledgeBase` (30-08), `SupportEscalationAssignment` (30-09 — workload + auto-assign/balance), `SupportAuditResolutionReport` (30-10 — số từ support_reports + export).
- API: `routes/api.php` prefix `platform/support` (middleware `platform.admin`), 3 controller `App\Http\Controllers\Platform\Support\*`.
- Test `tests/Feature/Batch10SupportApiTest.php`.

**Bẫy đã trả giá:** (1) Cột Filament closure `fn (string $s)` → 500 "unresolvable [$s]" (bug #1 quen thuộc) — sửa hết sang `$state` ở 5 page. (2) FK auto-name `data_correction_affected_records_data_correction_request_id_foreign` = 66 ký tự > giới hạn 64 của MySQL — đặt tên FK ngắn thủ công. (3) archive clone dùng driver-aware (MySQL LIKE / khác AS SELECT) như Batch 08.

**Verify:** `php -l` toàn bộ = 0 lỗi; `migrate:fresh --seed` sạch (000027 OK, seed ~7s); đếm seed đúng ảnh (priority 12/46/132/120, escalated 28, near-breach 37, snapshot 88.4/11.6/4.6, report 1248/14h36m/96.8/312/24/4.7, 4 team/29 member); **10/10 màn render HTTP 200** (8 bespoke `/admin` + `/fila`); **Batch10 API test 10/10 PASS (37 assertion)**; Batch07 10/10 + Batch08 11/11 PASS (không hồi quy).

**Còn lại (tùy chọn):** Escalation dạng bảng + workload cards (chưa dựng Kanban kéo-thả); AI suggest KB theo ngữ cảnh ticket (chưa nối X2AI); wizard là action theo bước (chưa stepper trang riêng đầy đủ); API auth qua phiên Filament/actingAs (chưa Sanctum stateless).

**Tiếp:** BQL-4 Tài chính (WEB-FORM-08) hoặc gắn Sanctum cho API platform (Batch 07/08/10).

---

## 2026-07-01 — Batch 08: Integration Center, API Key & Webhook (10 màn, platform-level)

**Phạm vi:** Trọn gói Batch 08 (M1→M6): 18 bảng, 18 model, 17 resource `/fila`, 7 màn bespoke `/admin` (phủ 10 screen WEB-UX-28), API `platform/integrations` + 11 test. Reconcile drop `integration_connections` per-tenant cũ.

**Files chính:**
- Migration `database/migrations/2026_07_01_000026_create_integration_center_batch08.php` (18 bảng + archive clone; drop bảng cũ + recreate ở down()).
- Models: `app/Models/Integration*.php` + `Webhook*.php` (18). `IntegrationConnection` viết lại thành platform-level (bỏ BelongsToTenant).
- Service `app/Support/Integration/IntegrationSecret.php` (Crypt encrypt/decrypt · sha256 hash · mask · generate). Trait `app/Filament/Concerns/WritesIntegrationAudit.php`.
- Seed `DemoDataSeeder::seedBatch08Integration` (7 category, 12 connection + credential + 36 check, 4 API key + 8 scope, 12 event group, 5 webhook + 12 delivery, 10 event, 3 retry job, 2 incident, 8 security policy, 4 IP allowlist, rate limit).
- `/fila`: 17 resource sinh bằng `make:filament-resource --generate`; codemod set nav group 'Integration Center' + **strip secret fields** (secret_hash/encrypted_payload/signing_secret_hash) khỏi form/table; soft-delete UX cho 4 resource soft-deletable.
- Bespoke `/admin` (nav group 'Integration Center', gate `PlatformScreen`): `IntegrationOverviewDashboard` (28-01), `ExternalConnectionManagement` (28-02/03, detail modal), `ApiKeyManagement` (28-04/05), `WebhookEndpointManagement` (28-06/07, test + delivery history), `EventLogMonitor` (28-08, filter/replay/export), `IntegrationHealthRetryQueue` (28-09, retry/skip/dead-letter + incident timeline), `IntegrationSecuritySettings` (28-10, save/enforce-hmac/rotate-expiring/emergency-disable/IP allowlist).
- API: `routes/api.php` prefix `platform/integrations` (middleware `platform.admin`), 7 controller `App\Http\Controllers\Platform\Integration\*`. Secret trả về MỘT LẦN khi create/rotate.
- Test `tests/Feature/Batch08IntegrationApiTest.php`.

**Nguyên tắc bảo mật (đạt):** secret không lưu plain-text — credential dùng `Crypt::encryptString` (payload) + masked_summary; API key/webhook lưu `sha256` hash + masked; secret chỉ hiện 1 lần (Notification persistent / API response create/rotate). Mọi hành động đổi trạng thái ghi `integration_audit_logs`. Emergency disable cần lý do + `isPlatformAdmin`. Replay idempotent (không tạo retry job trùng theo event_id).

**Bẫy đã trả giá:** `CREATE TABLE … LIKE` (archive clone) chỉ đúng MySQL → vỡ trên sqlite test (RefreshDatabase chạy mọi migration). Fix: branch theo `DB::getDriverName()` — MySQL `LIKE`, driver khác `AS SELECT … WHERE 1=0`. Áp cho cả migration 000025 lẫn 000026. (Ngoài ra: `use` import phải ở đầu routes/api.php, không append cuối file.)

**Verify:** `php -l` toàn bộ model/resource/page/controller/test = 0 lỗi; `migrate:fresh --seed` sạch (000026 ≈ 1s, seed 12s); seed đếm đúng (12 connection, 4 key, 5 webhook…), credential `encrypted_payload` là Crypt blob + api key hash dài 64; **10/10 màn render HTTP 200** (7 bespoke `/admin` + `/fila`); **Batch08 API test 11/11 PASS (49 assertion)**; Batch07 test **10/10 PASS** (không hồi quy).

**Còn lại (tùy chọn):** ConnectionDetail/ApiKeyCreate hiện là modal trong trang quản lý (không tách page riêng); StaffProfilesTable (batch trước) chưa gắn trashed UX; API xác thực qua phiên Filament/actingAs (chưa gắn Sanctum stateless — như Batch 07); provider connector thật (hiện test/health là mô phỏng).

**Tiếp:** BQL-4 Tài chính (công nợ WEB-FORM-08) hoặc gắn Sanctum cho API platform.

---

## 2026-07-01 — Soft Delete toàn hệ + Global scope tầng Project + Archive log

**Phạm vi:** Nền tảng CSDL/ORM — thêm soft delete cho toàn bộ bảng nghiệp vụ (trừ log/pivot), global scope tầng dự án `BelongsToProject`, xử lý unique index, UX khôi phục ở `/fila`, và cơ chế archive log.

**Files:**
- Migration ADD-ONLY `database/migrations/2026_07_01_000025_add_soft_deletes_and_archive.php`.
- Trait `app/Models/Concerns/BelongsToProject.php` (mới); `app/Filament/Concerns/SoftDeletableResource.php` (mới).
- `config/archive.php` (mới); `app/Console/Commands/ArchiveStaleLogs.php` (mới, lệnh `logs:archive`); `routes/console.php` (đăng ký schedule dailyAt 02:30).
- **156 model** `app/Models/*` +`use SoftDeletes`; **17 model** thuộc set vận hành + `use BelongsToProject` (Apartment, Resident, Vehicle, AccessCard, ResidentApprovalRequest, FeedbackRequest, WorkOrder, Statement, BillingRun, BillingPeriod, Payment, Debt, IocAlert, Department, Area, Floor, Team).
- **82 Resource** `/fila` +`use SoftDeletableResource`; **81 Table** +TrashedFilter+Restore/ForceDelete (record & bulk). (StaffProfilesTable non-standard → chưa gắn, làm tay sau.)

**Quyết định (chốt với chủ dự án):**
1. Soft delete cho **tất cả trừ log/pivot** (DENY set trong migration + codemod: framework, log/append-only/ledger, pivot thuần).
2. `BelongsToProject` **opt-in, đa dự án**: auto-detect cột — `project_id` nếu có, else `building_id ∈ (buildings của các dự án user được phép)`. No-op ở console + platform admin + tenant operator (HQ thấy mọi dự án trong tenant); BQL scope theo `accessibleProjectIds()`. Bypass `withoutGlobalScope('project')`. (Đa số bảng scope theo `building_id` chứ không có `project_id` — chỉ blocks/buildings/teams/users/ai_* có cột project_id thật.)
3. Archive `*_archive` (CREATE TABLE LIKE) + lệnh `logs:archive` dọn định kỳ theo `config/archive.php` (retention/ table).

**Unique index:** rebuild 4 unique nghiệp vụ có nguy cơ đụng khi soft delete → composite `[col, deleted_at]`: `buildings.code`, `projects.code`, `tenants.code`, `users.email` (NULL distinct ⇒ 1 bản live/khoá, N bản trashed).

**Bẫy đã trả giá:** `Schema::getTableListing()` (Laravel 13/MySQL) trả tên **schema-qualified** (`db.table`) ⇒ `in_array($t, $deny)` không khớp ⇒ lần chạy đầu thêm `deleted_at` vào MỌI bảng (kể cả cache/jobs/permissions/_archive). Fix: strip prefix trước khi so deny.

**Verify:** `php -l` toàn bộ model + resource + table = **0 lỗi**; `migrate:fresh --seed` sạch (000025 ≈ 4s, seed OK). Smoke `scratchpad/smoke.php` **14/14 PASS** (deleted_at đúng chỗ, deny sạch, archive tồn tại, delete/restore/withTrashed, composite unique tái tạo email trashed, 'project' scope wired đúng set — FeeType KHÔNG có). `scratchpad/archive_test.php`: deny+archive sạch, `logs:archive` chuyển đúng dòng cũ (2020) sang archive, dòng mới nguyên vẹn. Render HTTP: `/fila/apartments|statements|work-orders|residents` = **200** (login platform admin).

**Còn lại:** StaffProfilesTable gắn tay; cân nhắc soft delete cho thêm bảng con nếu cần; project-scope mới phủ set vận hành lõi — mở rộng khi có nhu cầu. Chưa chạy full test suite (`php artisan test`) sau đổi model — nên chạy ở phiên sau.

**Tiếp:** BQL-4 Tài chính (công nợ WEB-FORM-08 + duyệt chi + biên lai).

---

## 2026-07-01 — BQL-3: Trung tâm thông báo (soạn + phạm vi 3 lớp + hiệu quả)

**Files:** `app/Filament/Pages/NotificationCenter.php`; blades `notification-center.blade.php` + `notification-detail.blade.php`.

**Tóm tắt:** Page HasTable trên `notifications` **theo quyền xem** (`Notification::scopeVisibleTo`, 3 lớp). KPI (đã phát hành/hẹn giờ/nháp/tỉ lệ đọc). Header action **Soạn thông báo** (RichEditor + loại/ưu tiên + **phạm vi 3 lớp**: scope options theo cấp user [platform: all/tenant/project/building · công ty: project/building/apartment · BQL: building/apartment] + target select động qua `Get` + kênh app/email/sms/zalo + phát hành ngay / hẹn giờ). owner gán theo cấp (`creatorOwner`), tạo `notification_audiences` + `notification_channels`. Row actions: **Chi tiết** (modal: nội dung + phạm vi + kênh + người nhận/đã đọc/đã gửi), **Phát hành** (`applyPublish` ước tính người nhận theo scope: building/apartment/project/tenant/all → residents count), **Lưu trữ** — gate `canManageBy`. Audit đầy đủ.

**Bẫy (lặp lại lần 3):** cột Filament closure `fn (string $s)` → 500 "unresolvable [$s]"; đổi hết sang `$state`.

**Verify:** `php -l` sạch; `view:cache` OK; render `/admin/notifications/center` → **HTTP 200**; script: composeSchema dựng 10 field; tạo NHÁP (audiences=1/channels=2), applyPublish → published + recipient_count=121, publish-now → 178, detail modal render, audit ghi nhận.

**Tiếp:** BQL-4 Tài chính (công nợ WEB-FORM-08 + duyệt chi/đề nghị thanh toán + biên lai).

---

## 2026-07-01 — BQL-2: Bảng công việc Kanban (kéo-thả + checklist + nghiệm thu)

**Files:** `app/Filament/Pages/WorkOrderKanban.php`; blades `work-order-kanban.blade.php` + `work-order-detail.blade.php`.

**Tóm tắt:** Page bespoke (không HasTable) — 4 cột theo `WorkOrderStatus` (Chờ/Đang/Hoàn thành/Quá hạn), thẻ công việc **kéo-thả bằng HTML5 draggable + Alpine** (`dragId`), thả gọi `moveCard($id,$status)` (→ set started_at/completed_at, ghi audit). Scope theo dự án (`CurrentContext::buildingIds`). Thẻ hiện code/tiêu đề/ưu tiên/người xử lý/tiến độ checklist. Action theo thẻ qua `mountAction(name,{id})` (render ẩn 4 action): **Chi tiết** (modal checklist/đính kèm/chữ ký/giao việc), **Giao việc**, **Checklist** (CheckboxList tick mục → cập nhật is_done/done_by/done_at), **Nghiệm thu** (tạo `work_order_signatures` + set done). Không cần thư viện ngoài (native DnD).

**Bẫy:** eager-load `'category'` trên WorkOrder lỗi — `category` là CỘT string, không phải quan hệ (WorkOrder chỉ có `department()`); đã bỏ khỏi `with()`.

**Verify:** `php -l` sạch; `view:cache` OK; render `/admin/work-orders/kanban` → **HTTP 200**; script: buildingIds=[1,2], `moveCard`→in_progress (started_at set) →done (completed_at set), status bogus bị chặn (no-op), detail modal render (có checklist), audit ghi nhận.

**Tiếp:** BQL-3 Thông báo (center + soạn + audiences 3 lớp + hiệu quả đã đọc/gửi).

---

## 2026-07-01 — BQL-1: Hàng đợi & xử lý phản ánh (bespoke /admin)

**Phạm vi:** Màn vận hành BQL đầu tiên (QL-FB-01..03) — luồng phản ánh end-to-end.

**Files:** `app/Filament/Pages/FeedbackQueue.php`; blades `feedback-queue.blade.php` + `feedback-detail.blade.php`; nav group 'Vận hành' vào AdminPanelProvider.

**Tóm tắt:** Page HasTable trên `feedback_requests` **scope theo dự án** (`CurrentContext::buildingIds`). KPI (chờ/quá hạn SLA/đã xử lý/đã đóng) + phân bố theo danh mục (bar). Row actions: **Chi tiết** (modal timeline gộp comment/assignment/status_history + tệp + đánh giá), **Trao đổi** (comment nội bộ), **Giao việc** (→ `feedback_assignments` + status Assigned + history), **Tạo công việc** (→ `work_orders` link `feedback_request_id`), **Bắt đầu/Đã xử lý/Đóng** (chuyển trạng thái + `feedback_status_histories`; đóng kèm rating), bulk Giao việc. Mọi hành động ghi `audit_logs` (WritesAudit) + đẩy ngữ cảnh X2AI.

**Bẫy:** `Livewire\Component` đã có method public `transition()` → đặt tên private `transition()` bị Fatal "must be public". Đổi thành `changeStatus()`. (Ghi nhớ: tránh trùng tên method Livewire: mount/render/transition/dispatch...)

**Verify:** `php -l` sạch; `view:cache` OK; render `/admin/feedback/queue` → **HTTP 200**; script logic: assign→Assigned (+assignment+history), changeStatus start→resolved (resolved_at set, history tăng), createWorkOrder → `WO-FB-1` link đúng, detail modal render (có timeline), audit ghi nhận.

**Tiếp:** BQL-2 Công việc (Kanban) — tái dùng detail/timeline pane.

---

## 2026-07-01 — Addendum SuperAdmin / P2–P6: Platform Library + AI Governance (HOÀN TẤT addendum)

**Files:** migrations `..._000019..000023`; ~25 models mới + resource /fila; các seed method `seedPlatformContent/seedGlobalAccounts/seedSharedPartners/seedDocumentTemplates/seedKbAiGovernance`.

**Tóm tắt theo slice:**
- **P2 Platform content:** `platform_content_categories`, `platform_contents` (CMS tin/banner/guide, publish_scope), `public_projects` (+`project_media`), `tenant_project_links`.
- **P3 Global account & binding:** `global_user_accounts` (registry public→verified→resident), `resident_binding_requests`, `resident_unit_bindings` (bổ trợ users/residents; 1 user ↔ N căn).
- **P4 Shared partner library (platform):** `shared_partner_categories`, `shared_partners` (+`certifications`,+`products`), `tenant_partner_assignments` (approved/contracted/blacklist/favorite) — khác `contractors`/`service_providers` per-tenant.
- **P5 Document template library:** `document_template_categories`, `document_templates` (+`shares` view_only|use_as_template|clone_allowed|force_apply, +`clones`), owner_scope 3 cấp.
- **P6 KB/AI governance:** `knowledge_documents` (+`knowledge_scopes`, sensitivity+ai_index_status), `ai_guardrail_policies`, `ai_retrieval_logs`; **mở rộng** `ai_prompt_templates` (code/use_case/system_prompt/user_prompt_template/variables_json/owner_scope). Giữ `knowledge_articles` làm KB vận hành per-tenant (có UI + X2AI search).

**Reconcile:** `ai_prompt_templates` mở rộng (không tạo trùng); `knowledge_documents` = tầng KB governance nền tảng, tách với `knowledge_articles` vận hành; `ai_guardrail_policies`/`ai_retrieval_logs` bổ sung cạnh `ai_policies`/`ai_usage_logs`.

**Verify:** mỗi slice `php -l` sạch + `migrate:fresh --seed` sạch + render /fila → **HTTP 200**. **Tổng 209 bảng.** Đợt này chỉ data-model + /fila + FeatureGateService; **12 màn WEB-UX-22 bespoke chưa dựng** (đợt sau).

---

## 2026-07-01 — Addendum SuperAdmin / P1: chuẩn hoá Feature-gate (reconcile)

**Quyết định (chủ dự án):** addendum = spec chuẩn → reconcile; đợt này chỉ data-model (+seed +/fila +service), chưa dựng 12 màn WEB-UX-22.

**Files:** migration `..._000018_reconcile_feature_gate`; XOÁ models `SaasPlan`/`TenantModule` + resources SaasPlans/TenantModules; models mới `Module`,`Feature`,`Plan`,`PlanFeature`(mới),`TenantEntitlement`,`TenantModuleOverride`; sửa `Subscription` (plan_id→Plan); `App\Support\Platform\FeatureGateService`; sửa `DemoDataSeeder::seedTier4Saas`; regenerate Subscription resource + 4 resource mới.

**Tóm tắt:** thay first-cut `saas_plans/plan_features/tenant_modules` (STARTER/PRO/ENT) bằng mô hình addendum: `modules`(M01–M12)+`features`, `plans`(popular/full/intelligent)+`plan_features`(pivot+limits), `tenant_entitlements`, `tenant_module_overrides`; `subscriptions.saas_plan_id`→`plan_id`. `FeatureGateService` giải quyền theo thứ tự plan_features + entitlements + overrides − hết hạn/khoá.

**Verify:** `php -l` sạch; `migrate:fresh --seed` sạch (12 module / 30 feature / 3 plan / 76 plan_feature / 30 entitlement); gate: tenant demo (gói intelligent) có 28 feature, hasFeature(x2ai/rag)=yes, moduleEnabled(M10 override)=no ✓; render 5 /fila → **HTTP 200**.

**Tiếp:** P2 platform content · P3 global account/binding · P4 shared partners · P5 document templates · P6 KB/AI governance.

---

## 2026-07-01 — Slice B7: đóng nốt gap → PHỦ 100% CANONICAL_ENTITY_MAP

**Files:** migration `..._000017_close_entity_gaps`; models `ActivityLog`, `AiRequest`, `AiApproval`, `AutomationStep`, `KnowledgeChunk`; `seedEntityGapClose()`; 3 resource /fila (ActivityLog, AiApproval, AiRequest).

**Tóm tắt:** `activity_logs` (T1, C9) + T6 `ai_requests`, `ai_approvals` (human-in-the-loop), `automation_steps` (bảng hoá steps), `knowledge_chunks` (RAG). Seed: 5 activity, ai_requests từ ai_usage_logs, ai_approvals từ log pending_approval, automation_steps từ steps JSON, knowledge_chunks từ content_text.

**Verify:** `php -l` sạch; `migrate:fresh --seed` sạch; render 3 /fila → **HTTP 200**.

**✅ HOÀN TẤT TOÀN BỘ ENTITY:** T1 21/21 · T2 40/40 · T3 31/31 · T4 28/28 · T5 25/25 · T6 14/14. **185 bảng.** Mọi entity trong CANONICAL_ENTITY_MAP đã có migration + model + seed; các entity chính đã có resource /fila mặc định. Phân quyền 3 lớp (platform/tenant/project) áp cho Notification + KB (scopeVisibleTo) và cột tenant/project/building trên bảng vận hành.

---

## 2026-07-01 — Batch B / Slice B6: Tier 5 Marketplace/Loyalty/Dịch vụ/BĐS/Smart Home (HOÀN TẤT Tier 5)

**Files:** migration `..._000016_create_marketplace_ecosystem`; 15 models (`MarketplaceProduct/Order/OrderItem`, `ServiceProvider/ServiceOrder`, `LoyaltyAccount/Transaction`, `Voucher`, `RealEstateListing/ListingInquiry`, `SmartHomeAccount/SmartDevice/SmartScene/SensorEvent/EnergyReading`); `seedTier5Ecosystem()`; 8 resource /fila.

**Tóm tắt:** marketplace_products/orders(+items), service_providers/orders, loyalty_accounts/transactions, vouchers, real_estate_listings/inquiries, smart_home_accounts/devices/scenes/sensor_events/energy_readings. Seed đầy đủ demo mỗi bảng.

**Verify:** `php -l` sạch 17 file; `migrate:fresh --seed` sạch; render 8 /fila → **HTTP 200**.

**✅ Tier 5 HOÀN TẤT (25/25). Batch B xong. Tổng 180 bảng.** Coverage: T1 20/21 · T2 40/40 · T3 31/31 · T4 28/28 · T5 25/25 · T6 10/14. Còn: `activity_logs` (optional), T6 `ai_requests/ai_approvals/automation_steps/knowledge_chunks` (hoãn — xem B7).

---

## 2026-07-01 — Batch B / Slice B5: Tier 5 Bàn giao/Bảo hành + Cộng đồng

**Files:** migration `..._000015_create_handover_community`; 12 models (`HandoverBatch/Unit/Checklist/PunchItem`, `WarrantyRequest`, `CommunityGroup/Post`, `Event/EventRegistration`, `Poll/Option/Vote`); `seedTier5Community()`; 5 resource /fila (HandoverBatch, WarrantyRequest, CommunityPost, Event, Poll).

**Tóm tắt:** handover_batches(+units,+checklists,+punch_items), warranty_requests, community_groups/posts, events(+registrations), polls(+options,+votes). Seed 1 đợt bàn giao 6 căn + checklist/punch, 2 bảo hành, 1 nhóm + 3 post, 1 sự kiện + đăng ký, 1 poll + 3 lựa chọn + votes.

**Verify:** `php -l` sạch; `migrate:fresh --seed` sạch; render 5 /fila → **HTTP 200**.

---

## 2026-07-01 — Batch B / Slice B4: Tier 4 Form Builder (HOÀN TẤT Tier 4)

**Files:** migration `..._000014_create_form_builder`; models `DynamicForm`, `FormVersion`, `FormSection`, `FormField`, `FormWorkflow`, `FormSubmission`, `FormSubmissionValue`; `seedTier4FormBuilder()`; 3 resource /fila (DynamicForm, FormField, FormSubmission).

**Tóm tắt:** `dynamic_forms`(+versions,+sections,+fields,+workflows) + `form_submissions`(+values). Seed 2 biểu mẫu (published) + section/fields/workflow + 2 lượt nộp/mỗi form.

**Verify:** `php -l` sạch; `migrate:fresh --seed` sạch; render 3 /fila → **HTTP 200**.

**✅ Tier 4 HOÀN TẤT (28/28). Tổng 153 bảng.** Còn Tier 5 (ecosystem 0/25).

---

## 2026-07-01 — Batch B / Slice B3: Tier 4 Nhà thầu + Tài sản + Đồng hồ + IoT

**Files:** migration `..._000013_create_contractors_assets_meters`; 12 models (`Contractor`, `Contract`(+`Package`/`Acceptance`), `ContractorKpi`, `ContractorSettlement`, `AssetCategory`, `Asset`, `MaintenancePlan`, `Meter`(+`Reading`), `IotDevice`); `seedTier4AssetsContractors()`; 7 resource /fila.

**Tóm tắt:** contractors/contracts(+packages,+acceptances) (C7), contractor_kpis, contractor_settlements, asset_categories/assets, maintenance_plans, meters(+readings), iot_devices. Seed 2 nhà thầu + hợp đồng/gói/nghiệm thu/kpi/quyết toán, 4 nhóm + 6 tài sản, 2 kế hoạch bảo trì, 4 đồng hồ + chỉ số, 4 IoT.

**Verify:** `php -l` sạch; `migrate:fresh --seed` sạch; render 7 /fila → **HTTP 200**.

---

## 2026-07-01 — Batch B / Slice B2: Tier 4 Admin ops

**Files:** migration `..._000012_create_admin_ops`; models `SupportTicket/Comment`, `DataFixRequest`, `ImportJob`, `ExportJob`, `IntegrationConnection`, `PaymentGatewayConfig`; `seedTier4AdminOps()`; 6 resource /fila.

**Tóm tắt:** `support_tickets`(+comments), `data_fix_requests`, `import_jobs`, `export_jobs`, `integration_connections`, `payment_gateway_configs`. Seed 3 ticket, 2 data-fix, 2 import + 2 export, 3 integration, 2 gateway.

**Verify:** `php -l` sạch; `migrate:fresh --seed` sạch; render 6 /fila → **HTTP 200**.

---

## 2026-07-01 — Batch B / Slice B1: Tier 4 SaaS billing

**Files:** migration `..._000011_create_saas_billing`; models `SaasPlan/PlanFeature`, `Subscription`, `SubscriptionInvoice/Line`, `TenantModule`, `UsageMetering`; `seedTier4Saas()`; 4 resource /fila (SaasPlan, Subscription, SubscriptionInvoice, TenantModule).

**Tóm tắt:** `saas_plans`(+features, platform-global), `subscriptions`, `subscription_invoices`(+lines, C2), `tenant_modules`, `usage_metering`. Seed 3 gói, 1 thuê bao Enterprise + 2 hóa đơn, 5 module, 4 metric usage.

**Verify:** `php -l` sạch; `migrate:fresh --seed` sạch; render 4 /fila → **HTTP 200**.

---

## 2026-07-01 — Batch A / Slice A4: Tier 3 An ninh & thiết bị (HOÀN TẤT Tier 3)

**Files:** migration `..._000010_create_security_and_access`; models `PatrolRoute/PatrolCheckpoint/PatrolSession`, `SecurityIncident`, `SosAlert`, `AccessDevice`, `Camera`, `AlertAction`; `DemoDataSeeder::seedTier3Security()`; 5 resource /fila (PatrolRoute, SecurityIncident, SosAlert, AccessDevice, Camera).

**Tóm tắt:** `patrol_routes`(+`checkpoints`,+`sessions`), `security_incidents`, `sos_alerts`, `access_devices`, `cameras`, `alert_actions` (trên ioc_alerts, C10). Seed: 2 tuyến×4 chốt + session, 3 sự cố, 3 SOS, 4 access device, 5 camera, alert actions.

**Bẫy:** đặt tên quan hệ `guard()` trên model đụng `Eloquent\Model::guard(array $guarded)` → Fatal. Đổi thành `guardUser()`.

**Verify:** `php -l` sạch; `migrate:fresh --seed` sạch; render `/fila/{patrol-routes,security-incidents,sos-alerts,access-devices,cameras}` → **HTTP 200**.

**✅ Tier 3 HOÀN TẤT (data + /fila). Batch A (Tier 2 vá + Tier 3) xong.** Tiếp: Batch B (Tier 4 + Tier 5).

---

## 2026-07-01 — Batch A / Slice A3: Tier 3 Phê duyệt + Tài chính vận hành

**Files:** migration `..._000009_create_approvals_and_ops_finance`; models `ApprovalRequest/ApprovalStep`, `Fund/FundTransaction`, `PaymentRequest`, `CashVoucher`; `DemoDataSeeder::seedTier3Finance()`; 4 resource /fila (ApprovalRequest, PaymentRequest, CashVoucher, Fund).

**Tóm tắt:** `approval_requests` (đa bước, morph subject) + `approval_steps`; `funds` + `fund_transactions` (số dư luỹ kế); `payment_requests` (đề nghị chi); `cash_vouchers` (phiếu thu/chi). Seed: 2 quỹ, 4 đề nghị chi (mixed), phiếu chi+thu → giao dịch quỹ cập nhật số dư, 3 yêu cầu duyệt × 3 bước.

**Verify:** `php -l` sạch; `migrate:fresh --seed` sạch; render `/fila/{approval-requests,payment-requests,cash-vouchers,funds}` → **HTTP 200**.

**Tiến độ Tier 3:** ~26/31 (còn A4: patrol/security/sos/access_devices/cameras/alert_actions).

---

## 2026-07-01 — Batch A / Slice A2: Tier 3 Work Order đầy đủ + SLA + Ca trực

**Files:** migration `..._000008_work_orders_full_and_shifts`; models `WorkOrderAssignment/Checklist/ChecklistItem/Attachment/Signature`, `SlaPolicy`, `Shift`, `DutyRoster` + mở rộng `WorkOrder`; `DemoDataSeeder::seedTier3Ops()`; 4 resource /fila (WorkOrder, SlaPolicy, Shift, DutyRoster).

**Tóm tắt:** Làm giàu `work_orders` (project/apartment/assignee/team/description/category/scheduled/started/completed/cost) + con `work_order_assignments`, `work_order_checklists`(+`_items`), `work_order_attachments` (C6), `work_order_signatures`. `sla_policies` (C4 config). `shifts` + `duty_rosters`. Seed: 8 WO làm giàu + assignment/checklist(3 item)/attachment/signature; 4 SLA; 3 ca × 3 ngày roster.

**Verify:** `php -l` sạch; `migrate:fresh --seed` sạch; render `/fila/{work-orders,sla-policies,shifts,duty-rosters}` → **HTTP 200**.

**Tiến độ Tier 3:** ~20/31 (còn: approvals/funds/cash — A3; patrol/security/sos/devices/cameras — A4).

---

## 2026-07-01 — Batch A / Slice A1: vá nốt Tier 2 (5 bảng + model + seed + /fila)

**Phạm vi:** Lấp entity Tier 2 còn thiếu, kèm seeding + resource /fila mặc định.

**Files:** migration `..._000007_create_tier2_patch`; models `EmergencyAlert`, `QrPaymentToken`, `ServiceEvaluation`, `AccessLog`, `IntercomEvent`; `DemoDataSeeder::seedTier2Patch()`; 5 resource /fila (`make:filament-resource --generate --panel=fila`).

**Tóm tắt:** `emergency_alerts` (băng cảnh báo cư dân), `qr_payment_tokens` (QR thu phí), `service_evaluations` (đánh giá sau xử lý), `access_logs` (ra/vào), `intercom_events` (chuông cửa). Đều BelongsToTenant + scope project/building. Seed: 2 cảnh báo, 5 QR, 5 đánh giá, 12 access log, 5 intercom.

**Verify:** `php -l` sạch; `migrate:fresh --seed` sạch; 5 route /fila (index/create/edit) đăng ký; render `/fila/{emergency-alerts,access-logs,service-evaluations,qr-payment-tokens,intercom-events}` → **HTTP 200**.

**Tiến độ Tier 2:** 40/40 ✅ (đủ).

---

## 2026-07-01 — Tier 2 (Resident MVP): tạo trọn CSDL các entity còn thiếu (16 bảng)

**Phạm vi:** Lấp Tier 2 theo ENTITY_PRIORITY (MASTER handoff) — CHỈ tầng dữ liệu (migration + model + seed), chưa UI. Phân quyền 3 lớp bake vào schema.

**Files:**
- Migrations (mới): `..._000003_create_notifications`, `..._000004_create_amenities_bookings`, `..._000005_extend_feedback_and_children`, `..._000006_create_visitors_and_packages`.
- Models (mới): `Notification`(+`scopeVisibleTo`/`canManageBy`), `NotificationAudience/Channel/DeliveryLog/Read`, `Amenity/AmenitySlot/AmenityBooking/BookingQrPass`, `FeedbackComment/Attachment/Assignment/StatusHistory`, `VisitorRegistration/VisitorPass`, `PackageDelivery`. Sửa `FeedbackRequest` (+relations/casts).
- `DemoDataSeeder::seedTier2()`.

**Tóm tắt (tên canonical theo CANONICAL_ENTITY_MAP):**
- **Notification (C5)**: `notifications` (owner_level platform|tenant|project, tenant_id nullable cho platform) + `notification_audiences` (all/tenant/project/building/apartment/role/resident/user) + `notification_channels` + `notification_delivery_logs` + `notification_reads`. `scopeVisibleTo`/`canManageBy` theo 3 lớp (giống KB).
- **Amenity**: `amenities` + `amenity_slots` + `amenity_bookings` + `booking_qr_passes`. Scope tenant/project/building.
- **Feedback (C3)**: làm giàu `feedback_requests` (project_id/resident_id/user_id/code/description/channel/assigned_to/team/sla/resolved/closed/rating) + `feedback_comments`/`feedback_attachments`/`feedback_assignments`/`feedback_status_histories`.
- **Visitor (C12)**: `visitor_registrations` + `visitor_passes`. **Package**: `package_deliveries`.
- Mọi bảng vận hành mang tenant_id+project_id+building_id để RBAC 3 lớp lọc (platform tất cả · công ty toàn tenant · BQL dự án mình). Đã có Invoice/Fee/Payment/Receipt từ trước ⇒ Tier 2 data coi như đủ.

**Verify:** `php -l` sạch 24 file; `migrate:fresh --seed` sạch. Counts: notifications 5 / audiences 5 / channels 6 / delivery_logs 8 / reads 4 · amenities 4 / slots 8 / bookings 6 / qr 3 · feedback_comments 12 / attachments 3 / assignments 6 / histories 6 · visitor_reg 4 / passes 3 · packages 5. Relations traverse OK (amenity→slots/bookings→qrPass; feedback→comments/assignments/history/assignee; visitor→passes; notification→audiences/channels/reads + recipient/read count). **3-tier Notification::visibleTo**: superadmin 5/5; BQL thấy platform-published + tenant-published + toàn bộ dự án mình, không lộ draft cấp trên.

**Còn lại:** chưa có UI cho Tier 2 (đúng phạm vi yêu cầu — chỉ tạo CSDL). GuestPass = visitor_passes (đã có); PackageDelivery xong.

---

## 2026-07-01 — Fix lỗi SQL 1366 khi lưu content_text (PDF sinh UTF-8 không hợp lệ)

**Phạm vi:** Sửa `QueryException 1366 Incorrect string value '\xED\xA0\xBD...'` khi cập nhật/tạo bài KB có đính kèm PDF.

**Files đổi:** `app/Support/Knowledge/DocumentTextExtractor.php`.

**Tóm tắt:**
- **Nguyên nhân**: cột `content_text` LÀ utf8mb4 (chấp nhận emoji 4-byte hợp lệ), nhưng `smalot/pdfparser` trích ra **CESU-8 / lone surrogate** (`\xED\xA0\xBD\xED\xB4\xB4` = cặp surrogate của 🔴) — đây KHÔNG phải UTF-8 hợp lệ nên MySQL từ chối (1366), không phụ thuộc charset.
- **Sửa**: thêm `DocumentTextExtractor::clean()` = `iconv('UTF-8','UTF-8//IGNORE')` (bỏ chuỗi byte lỗi, GIỮ emoji hợp lệ) + strip ký tự điều khiển. Áp dụng ở `htmlToText()`, `fromPdf()` và output `build()` ⇒ mọi `content_text` (create + edit, cả seeder) đều là UTF-8 sạch trước khi lưu.

**Verify (script + DB thật):** `clean()` trên chuỗi có surrogate+emoji+ctrl → UTF-8 hợp lệ, giữ 🔴, bỏ surrogate & ctrl. `KnowledgeArticle::update(content_text=sanitized)` = OK; update bằng byte gốc vẫn lỗi 1366 (đúng kỳ vọng). `php -l` sạch. Code PHP có hiệu lực ngay ở request kế (không cần restart serve).

**Lưu ý:** không cần reseed (content_text seed sinh từ body sạch). Hard-refresh & thử upload lại.

---

## 2026-07-01 — Fix upload tệp KB bị 302 (php.ini máy ADMIN)

**Phạm vi:** Sửa lỗi upload tệp trên khung Livewire trả về **302** (không lưu được).

**Files đổi:** `C:\Users\ADMIN\.config\herd\bin\php84\php.ini` (ngoài repo).

**Tóm tắt:**
- **Nguyên nhân**: `FileUploadController@handle` gọi `Validator::validate()`; khi tệp bị PHP loại bỏ vì vượt `upload_max_filesize`/`post_max_size`, request (không phải JSON) → ValidationException → **redirect 302**. Máy này (profile **ADMIN**) vẫn để mặc định `upload_max_filesize=2M`, `post_max_size=8M` (bản vá trước đó nằm ở profile `chtch`, không áp cho ADMIN) → tệp >2MB bị chặn.
- **Sửa**: nâng `upload_max_filesize=20M`, `post_max_size=25M` trong php.ini mà máy nạp (`php_ini_loaded_file` = `C:\Users\ADMIN\.config\herd\bin\php84\php.ini`), rồi **restart `php artisan serve`** (process cũ giữ giá trị 2M cho tới khi khởi động lại).

**Verify:** `php -r ini_get(...)` → `upload_max_filesize=20M | post_max_size=25M`; kill process serve cũ (PID cũ) + chạy lại `php artisan serve` (server báo running); probe `/admin` → 302 (redirect login, bình thường). FileUpload KB đặt `maxSize(10240)` (10MB) < 20M nên quá cỡ sẽ báo lỗi ở client thay vì 302.

**Lưu ý:** nếu chạy qua Herd FPM/domain khác (không phải `php artisan serve`), FPM cũng cần restart để nạp php.ini mới. Hard-refresh trình duyệt trước khi thử lại.

**Cập nhật — nguyên nhân thứ 2 (temp dir):** sau khi nâng size vẫn 302, log server báo `PHP Warning: File upload error - unable to create a temporary file`. `upload_tmp_dir` để trống → PHP dùng system temp; system temp Windows vẫn ghi được, NHƯNG lỗi xuất hiện khi **khởi động `php artisan serve` từ tool Bash (Git Bash)** — Git Bash xuất `TMP`/`TEMP` kiểu MSYS (`/tmp`…) mà PHP trên Windows không tạo file được. Sửa: (1) ghim `upload_tmp_dir` + `sys_temp_dir` = `C:\Users\ADMIN\AppData\Local\Temp` trong php.ini (xác định, không phụ thuộc shell); (2) **luôn chạy `php artisan serve` từ PowerShell** (env Windows chuẩn), KHÔNG chạy từ Bash tool. Verify: `ini_get('upload_tmp_dir')` = `C:\Users\ADMIN\AppData\Local\Temp`, `tempnam` OK; server chạy lại từ PowerShell, `/admin` → 302 login.

---

## 2026-07-01 — KB 3 cấp + X2AI đọc nội dung tệp KB (tool search_knowledge)

**Phạm vi:** Phân quyền Cơ sở tri thức theo RBAC 3 tầng + để X2AI đọc/tra cứu tài liệu KB (gồm text từ PDF/DOCX) trong phạm vi quyền.

**Files đổi:**
- `composer.json` (+`smalot/pdfparser` ^2.12 — trích text PDF)
- `database/migrations/2026_07_01_000002_knowledge_3tier_ownership.php` (mới)
- `app/Models/KnowledgeArticle.php` (bỏ BelongsToTenant → `scopeVisibleTo` + `canManageBy`), `app/Models/KnowledgeArticleShare.php` (mới)
- `app/Support/Knowledge/DocumentTextExtractor.php` (mới), `app/Support/X2AI/X2aiKnowledgeConnector.php` (mới)
- `app/Support/X2AI/X2aiClient.php` (+`knowledgeSearchTool` + runTool), `app/Livewire/X2aiChat.php` (bật tool + system prompt)
- `app/Filament/Pages/AiKnowledgeBase.php` (query visibleTo, owner/share cột+filter, gán owner khi tạo, trích content_text, action Chia sẻ, gate canManageBy), `app/Filament/Pages/AiCenter.php` + `AiGovernance.php` (KB count theo visibleTo)
- `database/seeders/DemoDataSeeder.php` (owner_level/share/content_text + tài liệu platform + dự án khác)

**Tóm tắt:**
- **3 cấp sở hữu** `owner_level` platform|tenant|project + `share_mode` private|descendants|custom + bảng `knowledge_article_shares` (chia sẻ tùy chọn tới tenant/project). `tenant_id` nới thành nullable (tài liệu platform). `scopeVisibleTo($user)`: superadmin thấy tất cả; công ty (tenant-op) thấy mọi tài liệu công ty + dự án trong tenant + tài liệu platform chia sẻ xuống; BQL chỉ thấy tài liệu dự án mình + tài liệu công ty/platform chia sẻ xuống. `canManageBy()` gate sửa/chia sẻ. UI: cột Cấp/Chia sẻ, filter, action **Chia sẻ** (platform chọn công ty+dự án; công ty chọn dự án), owner gán tự động theo cấp người tạo.
- **X2AI đọc tệp**: `DocumentTextExtractor` trích text (PDF smalot · DOCX ZipArchive · HTML strip) → lưu `content_text` khi tạo/sửa bài. Tool `search_knowledge` (X2aiClient) → `X2aiKnowledgeConnector` tìm trong `KnowledgeArticle::visibleTo(user)` (tôn trọng quyền 3 cấp), trả text cho model. Tool luôn bật ở mọi lượt chat; system prompt hướng dẫn dùng + trích dẫn tên tài liệu.

**Bẫy (lặp lại):** cột Filament closure param PHẢI tên `$state` — đặt `$s` cho owner_level/share_mode → 500 "unresolvable [$s]". Đã sửa.

**Verify:** `php -l` sạch; `migrate:fresh --seed` sạch (22 bài: 3 platform/3 công ty/16 dự án + 2 dự án khác, 1 share row, content_text 22/22). Script phân quyền: Superadmin thấy 22/22; **BQL dự án thấy 20/22 (ẩn 2 bài dự án khác, leak=0)**, thấy platform+công ty chia sẻ xuống; tenant-op thấy đủ 19 tài liệu công ty+dự án. `canManageBy`: BQL không quản lý được tài liệu dự án khác/platform (OK). Form dựng `RichEditor`+`FileUpload`; shareFormSchema platform=3 select / tenant=2 select. Extractor htmlToText OK, pdfparser=yes, tệp thiếu → rỗng. `view:cache`+`npm run build` OK; **4 màn HTTP 200**.

**Còn lại:** DOC nhị phân cũ không trích được (chỉ PDF/DOCX). Trích text chạy đồng bộ lúc lưu (tệp lớn có thể chậm — cân nhắc queue sau). Chưa browser-test upload thật + tool trả lời trong chat.

---

## 2026-07-01 — KB: soạn HTML (RichEditor) + đính kèm PDF/DOC + click tiêu đề/danh mục ở listing

**Phạm vi:** Nâng cấp form & bảng Cơ sở tri thức (WEB-UX-09-04).

**Files đổi:**
- `database/migrations/2026_07_01_000001_add_attachments_to_knowledge_articles.php` (mới — cột `attachments` json)
- `app/Models/KnowledgeArticle.php` (cast `attachments => array`)
- `app/Filament/Pages/AiKnowledgeBase.php`
- `resources/views/filament/kb/article-view.blade.php` (mới — modal xem)

**Tóm tắt:**
- Ô **Nội dung** đổi `Textarea` → **`RichEditor`** (soạn HTML; toolbar gọn: bold/italic/underline/strike/h2/h3/list/link/blockquote/codeBlock/undo/redo).
- Thêm **`FileUpload` đính kèm nhiều tệp** PDF/DOC/DOCX (disk `public`, thư mục `kb-attachments`, `preserveFilenames`, `multiple`+`appendFiles`+`reorderable`, ≤10MB/tệp) → lưu mảng path vào `attachments`. Prefill khi sửa (`fillForm` thêm `attachments`).
- **Listing**: cột **Tiêu đề** bấm được → mở modal **Xem** (render HTML nội dung + danh sách tệp tải/mở được, qua `viewArticleAction()` + partial `filament.kb.article-view`); cột **Danh mục** bấm được → `filterByCategory()` set `tableFilters['knowledge_category_id']` lọc bảng.

**Verify:** `migrate` (thêm cột) OK; `php -l` sạch; `view:cache` + `npm run build` OK; **4 màn render HTTP 200**; script: `articleFormSchema` dựng `RichEditor(body)`+`FileUpload(attachments)` OK, `viewArticleAction` OK, partial render OK (có/không tệp), cast `attachments` round-trip 2 tệp + link hiển thị OK.

**Còn lại:** tệp đính kèm hiện chỉ **lưu + tải/mở**; muốn **X2AI đọc nội dung tệp** cần bước trích text PDF/DOC (chưa làm). Nút file-upload/RichEditor/modal mới verify ở mức dựng+render, nên bấm thử trên trình duyệt 1 lượt (upload thật + submit).

---

## 2026-07-01 — AI Engine: nối write-actions (A3) — tạo/sửa workflow, bật-tắt policy/prompt, CRUD bài KB

**Phạm vi:** Biến 3 màn AI Engine từ đọc-thuần thành có thao tác ghi dữ liệu (thật, có audit).

**Files đổi:**
- `app/Filament/Concerns/WritesAudit.php` (mới — helper ghi `audit_logs` cho page)
- `app/Filament/Pages/AiKnowledgeBase.php` + `resources/views/filament/pages/ai-knowledge-base.blade.php`
- `app/Filament/Pages/AiGovernance.php` + `resources/views/filament/pages/ai-governance.blade.php`
- `app/Filament/Pages/AiWorkflowAutomation.php` + `resources/views/filament/pages/ai-workflow-automation.blade.php`

**Tóm tắt:**
- **KB (09-04):** table actions đầy đủ — header `Thêm bài viết` + `Thêm danh mục` (modal schema), record `Sửa`/`Xuất bản`/`Lưu trữ`, bulk `Xuất bản`/`Lưu trữ`/`Xóa`. `syncCategoryCount()` cập nhật `knowledge_categories.articles_count` sau mỗi thay đổi; `published_at` set khi publish.
- **Governance (09-02):** header action `Thêm chính sách` (modal); nút **Bật/Tắt** từng policy (`togglePolicy`) ở tab Chính sách và từng prompt (`togglePrompt`) ở tab Prompt — Livewire `wire:click` (page = Livewire component).
- **Workflow (09-03):** header `Tạo workflow` (modal, set steps mặc định + project từ CurrentContext + created_by); per-workflow `Sửa` qua `mountAction('editWorkflow', { id })` (action method `editWorkflowAction()` + `fillForm` theo arguments, render ẩn `{{ $this->editWorkflowAction }}` để đăng ký modal); `Tạm dừng/Kích hoạt` (`toggleWorkflow`); `Chạy thử` (`runWorkflow` → ghi 1 `ai_workflow_runs` + tăng runs/success + last_run_at).
- Mọi hành động ghi 1 dòng `audit_logs` qua trait `WritesAudit`.

**Bẫy:** nút thao tác nằm trong `<x-slot:action>`/blade của bespoke page vẫn là Livewire → `wire:click` gọi method public OK; action modal có tham số dùng `mountAction(name, {args})` + method `nameAction()` (Filament v5 tự resolve), phải render `{{ $this->nameAction }}` (ẩn cũng được) để modal tồn tại.

**Verify:** `php -l` sạch 4 file; `view:cache` compile sạch; `npm run build` OK; **4 màn render HTTP 200**; script logic (kernel + auth): togglePolicy active↔inactive OK, togglePrompt OK, toggleWorkflow active→paused OK, runWorkflow runs_count+1 & +1 run row OK, KB create + syncCount OK, setStatus publish set published_at OK, xóa khôi phục count OK, 8 dòng audit ghi nhận.

**Còn lại:** form modal (header create + edit-workflow) mới verify ở mức render + closure; nên click thử trên trình duyệt 1 lượt. Steps của workflow chưa cho sửa trong form (giữ template mặc định).

---

## 2026-07-01 — X2AI Copilot: 2 icon (Mới + Lịch sử) lên header, input quay lại đáy

**Phạm vi:** Bố trí lại khung chat.

**Files đổi:**
- `app/Livewire/X2aiChat.php`
- `resources/views/components/x2/ai-fab.blade.php`
- `resources/views/livewire/x2ai-chat.blade.php`

**Tóm tắt:**
- Chuyển 2 nút **Cuộc trò chuyện mới** + **Lịch sử** lên cụm header (cạnh icon phóng to/đóng).
  Header nằm ngoài component Livewire → 2 nút gọi qua `@click="Livewire.dispatch('x2ai-new-chat'|'x2ai-history')"`;
  thêm `#[On('x2ai-new-chat')]` / `#[On('x2ai-history')]` cho `newChat()` / `toggleHistory()`.
- Đưa **ô input xuống đáy** (pinned), vùng dữ liệu/hội thoại lên trên cuộn. Bỏ hàng action cũ ở thân.
- max-height vùng cuộn chỉnh về `calc(66vh - 7.5rem)` (header + input đáy).

**Verify:** `php -l` sạch; `npm run build` (Node 22) OK; `view:cache` compile sạch. Logic phiên/lịch sử
đã verified ở entry trước (method không đổi, chỉ thêm listener event).

**Lưu ý:** hard-refresh trình duyệt.

---

## 2026-07-01 — X2AI Copilot: phiên chat + nút Lịch sử, đảo bố cục (input trên cùng) để scroll chắc chắn

**Phạm vi:** Lịch sử chat theo PHIÊN + sửa dứt điểm lỗi không scroll.

**Files đổi:**
- `database/migrations/2026_06_30_000013_create_ai_chat_sessions.php` (mới)
- `app/Models/AiChatSession.php` (mới), `app/Models/AiChatMessage.php` (+ quan hệ session)
- `app/Livewire/X2aiChat.php`
- `resources/views/livewire/x2ai-chat.blade.php`
- `resources/views/components/x2/ai-fab.blade.php`

**Tóm tắt:**
- **Phiên chat**: bảng `ai_chat_sessions` (title/surface/last_message_at, per user+tenant) + cột
  `ai_chat_session_id` trên `ai_chat_messages` (ADD-ONLY). Mỗi lần mở trang = bắt đầu phiên mới
  (tạo lazy ở tin nhắn đầu, title = prompt đầu, surface = màn hình). `mount()` KHÔNG còn nạp lịch sử
  phẳng — bắt đầu trống.
- **Nút Lịch sử** trên khung chat: `toggleHistory()` mở danh sách phiên (50 gần nhất, theo
  last_message_at); `loadSession($id)` mở lại phiên (verify user_id); `newChat()` tạo phiên mới.
- **Đảo bố cục (theo yêu cầu)**: ô input + hàng action (Lịch sử / Cuộc trò chuyện mới) **nổi trên cùng**;
  vùng dữ liệu/hội thoại tách riêng bên dưới, cuộn độc lập.
- **Scroll chắc chắn (không phụ thuộc build CSS)**: popover dùng inline `style="height:66vh"` (`:class`
  chỉ đổi width); vùng dữ liệu dùng inline `style="max-height:calc(66vh - 9.5rem)"` + `overflow-y-auto`.
- Fix: thiếu `use App\Models\AiChatSession` trong component (lỗi bị `report()` nuốt → phiên không tạo).

**Verify (tinker):**
- Fresh mount: messages=0, sessionId=null. Submit → tạo phiên #1, surface=`admin/residents`, 1 msg.
- Trang mới: bắt đầu trống; Lịch sử liệt kê đúng phiên (title/time); `loadSession` nạp lại đúng nội dung.
- `php -l` sạch; `migrate` tạo bảng + cột OK; `npm run build` (Node 22) OK; `view:cache` sạch.

**Lưu ý:** hard-refresh trình duyệt. Vì chiều cao/scroll giờ là inline-style (không qua Tailwind build),
không còn phụ thuộc cache CSS.

---

## 2026-06-30 — Sidebar: bỏ user card ở chân + ẩn thanh scroll

**Phạm vi:** Chrome sidebar Filament `/admin`.

**Files đổi:**
- `app/Providers/Filament/AdminPanelProvider.php`
- `resources/views/filament/hooks/sidebar-footer.blade.php` (xóa)
- `resources/css/filament/admin/theme.css`

**Tóm tắt:**
- Bỏ block người dùng (avatar + tên + chức danh) ở chân sidebar: gỡ render hook
  `PanelsRenderHook::SIDEBAR_FOOTER`; xóa blade `sidebar-footer`; dọn CSS chết
  (`.fi-sidebar-footer`, `.x2-user*`).
- Ẩn thanh scroll sidebar (vẫn cuộn được): `.fi-sidebar(-nav)` `scrollbar-width:none` +
  `::-webkit-scrollbar{display:none}`.

**Verify:**
- `php -l` sạch; `npm run build` (Node 22) OK; CSS build chứa `scrollbar-width:none` +
  `fi-sidebar-nav::-webkit-scrollbar`; không còn tham chiếu `sidebar-footer`/`SIDEBAR_FOOTER`.

**Lưu ý:** hard-refresh trình duyệt.

---

## 2026-06-30 — X2AI Copilot: lưu lịch sử chat theo tài khoản + fix scroll/input biến mất

**Phạm vi:** Lưu lịch sử chat per-account; sửa lỗi vùng nội dung không cuộn + ô input biến mất.

**Files đổi:**
- `database/migrations/2026_06_30_000012_create_ai_chat_messages.php` (mới)
- `app/Models/AiChatMessage.php` (mới)
- `app/Livewire/X2aiChat.php`
- `resources/views/livewire/x2ai-chat.blade.php`

**Tóm tắt:**
- **Lịch sử chat theo tài khoản**: bảng `ai_chat_messages` (tenant_id/user_id/role/content, ADD-ONLY) +
  model `AiChatMessage`. `mount()` gọi `loadHistory()` (100 lượt gần nhất của user, assistant render lại
  Markdown→html). `submit()` lưu lượt user, `pushAssistant()` lưu lượt assistant (best-effort, try/catch).
  History gửi cho API giới hạn 16 lượt gần nhất (`array_slice`) để chặn token phình.
- **Fix scroll + input biến mất**: nguyên nhân chuỗi `flex-1/min-h-0` không khóa được chiều cao qua
  ranh giới component Livewire → vùng cuộn nở theo nội dung, đẩy input ra ngoài `overflow-hidden`.
  Thêm trần cứng theo viewport cho vùng cuộn: `max-h-[calc(66vh_-_7.5rem)]` (panel 66vh − header/input)
  → luôn cuộn được và input luôn hiển thị, không phụ thuộc flex.

**Verify:**
- `php -l` sạch; `migrate` tạo `ai_chat_messages` OK; `npm run build` (Node 22) OK, CSS có
  `calc(66vh - 7.5rem)`; `view:cache` compile sạch.
- Tinker: `submit()` → 1 dòng DB; component mới `mount()` đọc lại đúng (role=user, nội dung khớp).

**Lưu ý:** hard-refresh trình duyệt (asset mới).

---

## 2026-06-30 — X2AI Copilot: chat 2 bước (ChatGPT-style), chiều cao 2/3 màn hình, fix upload file

**Phạm vi:** UX khung chat + sửa lỗi không tải được file đính kèm.

**Files đổi:**
- `app/Livewire/X2aiChat.php`
- `resources/views/livewire/x2ai-chat.blade.php`
- `resources/views/components/x2/ai-fab.blade.php`
- `C:\Users\chtch\.config\herd\bin\php84\php.ini` (ngoài repo — cấu hình máy dev)

**Tóm tắt:**
- Chiều cao mặc định khung chat đổi `h-[86.4rem] max-h-[85vh]` → **`h-[66vh]`** (2/3 màn hình);
  bản mở rộng vẫn `w-[50vw] h-[66vh]`. Nội dung cuộn, input ghim đáy (giữ nguyên).
- **Chat 2 bước kiểu ChatGPT**: tách `send()` → `submit()` (hiện bong bóng prompt NGAY, gom
  pendingText/screenText, set `awaitingReply`, KHÔNG gọi API) + `generate()` (gọi model, append reply).
  `generate()` được kích bởi `x-init="$wire.generate()"` trên phần tử "thinking" (key theo số message).
  Input/nút khóa khi `awaitingReply`. Tự cuộn xuống đáy qua event `x2ai-scroll` (dispatch ở
  submit + pushAssistant, Alpine `x-on:x2ai-scroll.window`). Gate/approval/log chuyển sang `generate()`.
- **Fix upload file**: nguyên nhân `upload_max_filesize=2M` (< rule 10MB) trong php.ini của Herd
  → ảnh >2MB bị PHP chặn trước khi tới Livewire. Nâng `upload_max_filesize=20M`, `post_max_size=25M`.

**Verify:**
- `php -l` sạch; `npm run build` (Node 22) OK; `view:cache` compile sạch.
- Tinker: `submit()` → 1 message (role=user), awaiting=1, input rỗng, pendingText giữ, KHÔNG gọi API;
  `generate()` guard no-op khi không awaiting. php.ini sau sửa: upload=20M post=25M.

**Lưu ý:** **phải restart `php artisan serve` (và Herd nếu chạy FPM)** để php.ini mới có hiệu lực.
Hard-refresh trình duyệt sau build.

---

## 2026-06-30 — X2AI Copilot: permission/risk gate + UX chat (input đáy, markdown, bỏ toggle, cao gấp đôi)

**Phạm vi:** Mục 3 governance gate + 4 yêu cầu UX khung chat.

**Files đổi:**
- `app/Support/X2AI/X2aiPolicyGate.php` (mới)
- `app/Livewire/X2aiChat.php`
- `database/seeders/DemoDataSeeder.php`
- `resources/views/livewire/x2ai-chat.blade.php`
- `resources/views/components/x2/ai-fab.blade.php`

**Tóm tắt:**
- `X2aiPolicyGate` (mới): quyết định từ RBAC + `ai_policies` (active, không hardcode):
  `canUse` (perm `ai.use`, mặc định mở nếu chưa seed), `dataLookupAllowed` (perm `ai.data_lookup`
  **và** đã cấu hình `X2AI_DATA_API_URL` — chưa có thì ở chế độ context để khỏi gọi tool stub),
  `effectiveMode`, `riskFor`, `requiresApproval` (high + chính sách risk/high active → cần duyệt),
  `guidelines` (đẩy các chính sách active vào system prompt).
- Seeder: tạo 2 permission `ai.use` / `ai.data_lookup`; cấp `ai.use` cho mọi role, `ai.data_lookup`
  cho company_admin/hq_finance/operations_director/building_manager/accountant/customer_service.
- `X2aiChat`: bỏ toggle (mode theo quyền, set ở `mount`/`send`); gate `canUse` (chặn → log `rejected`),
  `requiresApproval` (→ log `pending_approval`, không gọi model); `logUsage()` nhận thêm mode/status/risk/
  requiresApproval; reply render Markdown→HTML an toàn (`GithubFlavoredMarkdownConverter`, html_input=strip)
  lưu sẵn `html`; system prompt thêm guidelines + yêu cầu định dạng Markdown, bỏ wording toggle.
- UI: bỏ 2 nút chọn chế độ; bố cục flex — hội thoại cuộn phía trên, **input ghim đáy**; chiều cao
  mặc định **gấp đôi** (`h-[86.4rem] max-h-[85vh]`, vẫn cap viewport), nút Mở rộng giữ `w-[50vw] h-[66vh]`;
  thêm CSS `.x2ai-prose` (bảng/heading/list/code đẹp).

**Verify:**
- `php -l` sạch 3 file PHP; `php artisan view:cache` compile sạch; `npm run build` (Node 22) OK,
  class `86.4rem/85vh/50vw/66vh` có trong CSS.
- `migrate:fresh --seed` OK. Tinker: ai.use/ai.data_lookup tồn tại; super_admin canUse=yes,
  effectiveMode=context (chưa có data API); 6 chính sách active → guidelines; requiresApproval(high)=yes;
  Markdown sinh `<table>`+`<strong>`; `X2aiChat::mount()` chạy OK (mode=context).

**Lưu ý:** cần hard-refresh trình duyệt. Mode `data` (Mode 2) sẽ tự bật khi cấu hình `X2AI_DATA_API_URL`
và user có quyền `ai.data_lookup`.

---

## 2026-06-30 — X2AI Copilot: nối ai_usage_logs + UI khung chat 2 kích thước

**Phạm vi:** Module AI Copilot (WEB-UX-09) — audit usage thật + nâng cấp UI.

**Files đổi:**
- `app/Support/X2AI/X2aiClient.php`
- `app/Livewire/X2aiChat.php`
- `resources/views/components/x2/ai-fab.blade.php`

**Tóm tắt:**
- `X2aiClient::ask()` nay thu thập telemetry mỗi lượt: `lastInputTokens`/`lastOutputTokens`
  (cộng dồn qua vòng lặp tool-use), `lastLatencyMs`, `lastModel`, `lastStatus`
  (`success`/`failed` ở mọi nhánh: thiếu key, HTTP fail, exception).
- `X2aiChat::send()` sau mỗi lượt ghi 1 dòng `AiUsageLog` qua `logUsage()`:
  tenant/project/building/user (auto-scope `BelongsToTenant`), surface (title màn/URL DOM),
  mode, model, action, risk_level (data=medium · context=low), status, token in/out,
  latency_ms, prompt/response_excerpt, cost quy đổi VND theo giá list từng model.
  Bọc try/catch → lỗi ghi log không làm hỏng câu trả lời.
  ⇒ Màn AiGovernance (09-02) tab Audit và AiCenter (09-01) phản ánh usage THẬT, không chỉ seed.
- `ai-fab.blade.php`: chiều cao mặc định gấp đôi (`max-h-[21.6rem]` → `max-h-[43.2rem]`);
  thêm nút "Mở rộng" (Alpine `expanded`) → panel `w-[50vw] h-[66vh]` (½ rộng × ⅔ cao viewport),
  body `flex-1` cuộn trong; tắt → về compact.

**Verify:**
- `php -l` sạch cả 2 file PHP.
- `npm run build` (Node 22) OK; class tùy biến `50vw`/`66vh`/`43.2rem` có trong CSS build.
- Tinker insert/delete `ai_usage_logs`: `inserted id=91 cost=0.36 before=90` → `deleted; now=90` (schema khớp).

**Lưu ý:** dòng live tính giá haiku $1/$5 per M (chính xác hơn) nên rẻ hơn dòng seed ($3/$15).
Cần hard-refresh trình duyệt sau build.

**Còn lại liên quan:** mục 3 — permission/risk gate qua `ai_policies` (chưa làm).

---

## 2026-06-30 — Slice AI Engine: 7 bảng + 4 màn bespoke "X2 AI Engine" (WEB-UX-09-01→04)

**Phạm vi:** Dựng cả mục "X2 AI Engine" trên `/admin` (data-model-first đầy đủ, chủ dự án chốt cả 4 màn).

**Files đổi:**
- `database/migrations/2026_06_30_000011_create_ai_engine_tables.php` (mới)
- `app/Models/AiUsageLog.php`, `AiPolicy.php`, `AiPromptTemplate.php`, `AiWorkflow.php`, `AiWorkflowRun.php`, `KnowledgeCategory.php`, `KnowledgeArticle.php` (mới)
- `database/seeders/DemoDataSeeder.php` (`seedAiEngine`)
- `app/Providers/Filament/AdminPanelProvider.php` (nav group 'X2 AI Engine' + 'Tài chính – Phí')
- `app/Filament/Pages/AiCenter.php`, `AiGovernance.php`, `AiWorkflowAutomation.php`, `AiKnowledgeBase.php` + 4 blade `resources/views/filament/pages/ai-*.blade.php` (mới)
- `resources/views/components/x2/ai-fab.blade.php` (nghe `x2ai-open`), `app/Livewire/X2aiChat.php` (`#[On('x2ai-prefill')]`)

**Tóm tắt:**
- Migration ADD-ONLY 7 bảng: `ai_usage_logs` (audit từng lượt), `ai_policies`, `ai_prompt_templates`, `ai_workflows`(+steps json)/`ai_workflow_runs`, `knowledge_categories`/`knowledge_articles`. Model dùng `BelongsToTenant` (trừ `AiWorkflowRun`).
- Seed `seedAiEngine`: 90 usage log/30 ngày, 7 chính sách, 8 prompt, 6 workflow + runs, 6 danh mục / 17 bài KB.
- 4 Page bespoke, KPI/biểu đồ TÍNH từ DB (không hardcode): `AiCenter` (`ai/center`, 09-01), `AiGovernance` (`ai/governance`, 09-02 — tab Alpine, tab Audit = HasTable trên `ai_usage_logs`), `AiWorkflowAutomation` (`ai/workflows`, 09-03 — chọn workflow → canvas node từ `steps` + cấu hình + nhật ký), `AiKnowledgeBase` (`ai/knowledge`, 09-04 — HasTable bài viết + danh mục + Support Copilot CTA).
- Nút "Gợi ý nhanh"/Support Copilot → window event `x2ai-open` (FAB nghe `x-on:x2ai-open.window`) + Livewire `x2ai-prefill` → `X2aiChat::prefill()`.

**Verify:** `migrate:fresh --seed` sạch; `php -l` sạch; `getViewData()` chạy được cả 4; `view:cache` compile sạch; `npm run build` OK; **4 màn render HTTP 200** (đã đăng nhập admin, headless kernel).

**Lưu ý:** đây là khung đọc + duyệt; action ghi dữ liệu (tạo/sửa workflow, bật-tắt policy, thêm bài KB) CHƯA nối. (Usage logging thật + policy gate được bổ sung ở các entry phía trên.)

---

## 2026-07-01 — SuperAdmin WEB-UX-22 Slice 0+1: nền móng + xương sống định danh

**Phạm vi:** Khởi động track SuperAdmin (gói addendum). Slice 0 = nền móng gating; Slice 1 = luồng định danh (rule #1: tài khoản gốc → duyệt gắn căn → thành cư dân). Ưu tiên theo nghiệp vụ + độ đầy đủ dữ liệu.

**Files mới/đổi:**
- `app/Providers/Filament/AdminPanelProvider.php` — thêm nav group **'Nền tảng (SuperAdmin)'**.
- `app/Filament/Concerns/PlatformScreen.php` (mới) — trait gating: `canAccess()`/`shouldRegisterNavigation()`; SuperAdmin (isPlatformAdmin) thấy tất; HQ chỉ thấy khi `platformFeature()` được gói bật qua `FeatureGateService` (KHÔNG hardcode gói). Bẫy: KHÔNG redeclare property trait ở class con (default khác → Fatal "define the same property … incompatible") → dùng method `platformFeature()` override.
- `app/Filament/Pages/GlobalUserRegistry.php` + `resources/views/filament/pages/global-user-registry.blade.php` + `account-profile.blade.php` (mới) — **WEB-UX-22-04**. HasTable trên `global_user_accounts`, feature `global_account`. 5 KPI (tổng/định danh/chưa gắn căn/nghi trùng/khoá), lọc loại+định danh+toggle trùng/khoá, action: xem hồ sơ (modal: định danh + căn đã gắn + yêu cầu + nghi trùng), verify định danh, khoá (bắt lý do)/mở khoá, tạo yêu cầu gắn căn.
- `app/Filament/Pages/ResidentBindingQueue.php` + `resources/views/filament/pages/resident-binding-queue.blade.php` + `binding-detail.blade.php` (mới) — **WEB-UX-22-05**. HasTable trên `resident_binding_requests`, feature `resident_binding`. 4 KPI theo trạng thái, lọc trạng thái (mặc định pending)+vai trò, detail modal (hồ sơ + căn + minh chứng + cảnh báo trùng SĐT/email/căn + binding trước đó), action: Duyệt (→ tạo `resident_unit_binding` idempotent + `public_user`→`resident`), Yêu cầu bổ sung, Từ chối (bắt lý do), Phân công duyệt.
- `database/seeders/DemoDataSeeder.php` (`seedGlobalAccounts`) — enrich: 12 tài khoản (đa định danh/loại, 1 khoá, 1 cặp nghi trùng DUP-01, risk cao), 10 yêu cầu phủ đủ 5 trạng thái, 1 tài khoản gắn 2 căn (AC-07).

**Scope FK:** cột là `user_account_id` (KHÔNG phải `account_id`) — relation `account()` trỏ FK này.

**Verify:** `migrate:fresh --seed` sạch (accounts=12, requests=10 đủ 5 trạng thái, bindings=2); `php -l` sạch; `view:cache` compile; **2 màn render HTTP 200** (đăng nhập platform admin, headless kernel); script logic: Duyệt tạo binding + đổi type + idempotent, Từ chối có lý do, phát hiện trùng DUP-01, Verify, tạo yêu cầu, 4 dòng audit (binding.approve/reject/create + account.*). Đạt AC-01..08.

**NEXT:** Slice 2 = 22-01 Platform Content Dashboard (control tower, tổng hợp slice 1 + content). Rồi Slice 3 content (22-02/03), Slice 4 thư viện (22-06..09), Slice 5 KB/AI (22-10..12). Chưa browser-click modal submit.

---

## 2026-07-01 — SuperAdmin WEB-UX-22 Slice 2–5: HOÀN TẤT 12/12 màn

**Phạm vi:** Dựng nốt 10 màn SuperAdmin còn lại (bespoke `/admin`, nav group 'Nền tảng (SuperAdmin)', gate qua trait `PlatformScreen`). Làm lần lượt theo nghiệp vụ.

**Slice 2 — Control tower:**
- `PlatformContentDashboard` (`platform/dashboard`, 22-01) — 7 KPI + 3 chart (content theo loại / KB theo scope / TK mới theo tuần) + 3 worklist (content chờ duyệt / binding chờ / index AI lỗi) + quick actions. Tất cả tính từ DB.

**Slice 3 — Content nền tảng:**
- `PlatformContentCms` (`platform/content`, 22-02) — CRUD + vòng đời draft→pending_review→published→archived + duplicate; publish/archive gate `isPlatformAdmin` + audit. Thêm relation `creator`/`approver` vào PlatformContent.
- `PublicProjectLibrary` (`platform/public-projects`, 22-03) — CRUD dự án + uploadMedia + linkTenant (TenantProjectLink) + togglePublic; detail modal (media/tiện ích/công ty liên kết).

**Slice 4 — Thư viện dùng chung:**
- Trait `SharedPartnerLibrary` (Concerns) + `ContractorLibrary` (`platform/contractors`, 22-06) & `SupplierVendorLibrary` (`platform/suppliers`, 22-07) — 1 trait, 2 page khác `partnerType()`. verify/prefer/blacklist/assign; supplier thêm SP, contractor thêm chứng chỉ. AC-14 (blacklist không gán được nếu không override).
- `DocumentTemplateLibrary` (`platform/document-templates`, 22-08) — CRUD + activate/deprecate + **share (owner KHÔNG đổi, AC-17)** + **clone (mẫu mới owner mới, AC-18)**. Thêm relation `clones` vào DocumentTemplate.
- `TemplateInheritancePolicy` (`platform/template-inheritance`, 22-09) — HasTable trên shares + áp chính sách theo danh mục (đếm mẫu ảnh hưởng) + rollback; force_apply cần SuperAdmin (AC-19).

**Slice 5 — KB & AI Governance:**
- `PlatformKnowledgeBase` (`platform/knowledge-base`, 22-10) — CRUD KB + index/reindex AI + archive (bỏ index) + share (KnowledgeScope, ai_read). sensitivity + ai_index_status (AC-20/21/22).
- `AiKnowledgeConfig` (`platform/ai-knowledge-config`, 22-11) — HasTable prompt (withoutGlobalScope) + create/edit/test/toggle; guardrail list toggle qua `wire:click toggleGuardrail`. KPI token/blocked từ ai_retrieval_logs.
- `KnowledgeAuditLog` (`platform/knowledge-audit`, 22-12) — HasTable audit_logs (lọc theo prefix governance) + export CSV (streamDownload) + panel retrieval AI gần đây, `mountAction('retrievalDetail',{id})` xem tài liệu dùng/bị chặn + snapshot quyền + token (AC-25/26/27).

**Bẫy đã trả giá slice này:**
- Trait `table()` đụng `InteractsWithTable::table` → giải bằng `use InteractsWithTable, SharedPartnerLibrary { SharedPartnerLibrary::table insteadof InteractsWithTable; }`.
- Quên `use Filament\Pages\Page;` → "Class Page not found".
- **BelongsToTenant global scope** giới hạn platform admin (tenant_id=1) → thêm `withoutGlobalScope('tenant')` cho mọi query platform-wide (ResidentBindingRequest/ResidentUnitBinding/TenantProjectLink/TenantPartnerAssignment/AiPromptTemplate). AC-01.
- Blade không dùng được `static::$title` → truyền qua getViewData.

**Data enrich:** shared partners 7 nhà thầu (đủ preferred/verified/unverified/blacklisted) + 4 NCC (có SP catalog); public_projects 5 (media). 

**Verify:** `migrate:fresh --seed` sạch; `php -l` sạch cả 10 file; `view:cache` compile; **12/12 màn render HTTP 200**; 2 script logic (`logic_sa.php` định danh AC-01..08; `logic_sa2.php` content publish / project link / partner verify+assign / template share owner-giữ + clone / KB index / guardrail toggle / audit governance) — tất cả pass + ghi audit. Scripts ở scratchpad.

**CÒN LẠI (polish, chưa làm):** browser-click submit các modal form; nối index AI thật (hiện mô phỏng set indexed); retrieval simulator thật ở 22-11 (hiện test prompt = xem prompt ghép); API controllers/routes + tests tự động (PHPUnit) theo CLAUDE_CODE_TASK_PROMPT.

---

## 2026-07-01 — Batch 07 SaaS Billing (reconcile) — Round 1: tầng DB

**Quyết định owner:** Batch 07 = canonical → reconcile (bỏ bảng saas sơ khai cũ, thay bằng bộ đầy đủ). Làm theo rounds; Round 1 = DB + models + FeatureGate + seed + /fila.

**Migration `2026_07_01_000024_reconcile_saas_billing_batch07`:**
- DROP: `subscriptions`, `subscription_invoices`, `subscription_invoice_lines`, `usage_metering` (slice B1 cũ). GIỮ feature-gate layer (plans/plan_features/modules/features/tenant_entitlements/tenant_module_overrides).
- CREATE 19 bảng: plan_prices, subscription_contracts, tenant_subscriptions, subscription_items, subscription_addons, subscription_renewals, usage_meters, usage_periods, usage_records, quota_alerts, billing_invoices, billing_invoice_lines, billing_payments, billing_reconciliations, billing_adjustments, credit_notes, pass_through_wallets, pass_through_transactions, billing_audit_logs. `tenant_subscriptions.plan_id` → `plans` (catalog feature-gate hiện có).

**Models:** 19 model mới (KHÔNG dùng BelongsToTenant — billing cấp platform, SuperAdmin thấy tất → tránh bẫy global-scope). Xoá 4 model cũ + 2 /fila resource cũ (Subscriptions, SubscriptionInvoices).

**FeatureGateService:** `Subscription` → `TenantSubscription`; `current_period_start/end` → `start_date/end_date`. Verify tenant#1 vẫn 28 features (không đổi).

**Seed (`seedBatch07Billing`):** 6 tenant billing (TEN-0001..0006, đủ active/trial/pending_renewal/suspended) + contracts + subscriptions + items + 4 addon + 1 renewal + 8 usage_meter + kỳ USAGE-2026-05 (locked) + 14 usage_record (có overage) + 3 quota_alert + 2 invoice (partially_paid/issued) + 8 line + 1 payment + 4 wallet + 4 transaction + 3 adjustment + 1 credit_note + 9 plan_price.

**/fila resources:** sinh 15 resource `make:filament-resource --generate --panel=fila` (Plan đã có từ addendum). Tất cả list render HTTP 200.

**Verify:** `migrate:fresh --seed` sạch (9.4s); counts đúng (tenant_subscriptions=7, usage_records=14, invoices=2…); FeatureGate 28 features; /fila 8 resource render 200; /admin/dashboard + /admin/platform/dashboard + /admin/ai/center + /admin/residents vẫn 200 (reconcile không vỡ).

**NEXT:** Round 2 = 9 custom page `/admin` billing (SaaS Revenue Dashboard, Subscription Detail, Contract/Renewal, Usage Metering, Overage/Quota, Invoice Generation, Invoice Detail+Payment, Pass-through Wallet, Billing Audit+Adjustment) + các action (upgrade/downgrade/addon/lock-usage/generate-invoice/record-payment/reconcile/top-up/adjustment→credit-note) ghi `billing_audit_logs`. Round 3 = API `platform/billing/*` + PHPUnit tests.

---

## 2026-07-01 — Batch 07 SaaS Billing — Round 2: 9 custom page /admin

**Nav group mới 'SaaS Billing'.** Trait `WritesBillingAudit` (ghi `billing_audit_logs` before/after cho mọi hành động). Gate qua `PlatformScreen` (SuperAdmin/Billing admin). 9 màn bespoke:
- `SaasRevenueDashboard` (`billing/revenue`, 27-01) — MRR/ARR/churn/overage/overdue + MRR theo plan + top tenant + dự báo gia hạn (read-only, tính từ DB).
- `SubscriptionManagement` (`billing/subscriptions`, 27-02/03) — HasTable + đổi gói (up/down) / add-on / pause / resume / renew / cancel + detail modal.
- `ContractRenewalManager` (`billing/contracts`, 27-04) — HasTable HĐ + pipeline gia hạn (wire:click duyệt/từ chối) + mark expired / terminate.
- `UsageMeteringDashboard` (`billing/usage`, 27-05) — HasTable usage record + header action recalculate/lock/unlock/generateAlerts (period-lock workflow).
- `OverageQuotaAlert` (`billing/quota-alerts`, 27-06) — HasTable alert + assign/resolve/dismiss/convert-to-addon(tạo SubscriptionAddon+MRR)/convert-to-upgrade.
- `InvoiceGeneration` (`billing/invoice-generation`, 27-07) — page xem trước + generate hóa đơn nháp từ thuê bao+addon+overage(kỳ đã khóa)+VAT, bỏ qua đã có.
- `InvoiceManagement` (`billing/invoices`, 27-08) — HasTable + approve/send/void + recordPayment(partial→partially_paid, đủ→paid) + reconcile + detail modal.
- `PassThroughWalletDashboard` (`billing/wallets`, 27-09) — HasTable ví + topup/requestTopup/approveTopUp(wire:click)/deduct/configure auto-topup + cảnh báo số dư thấp.
- `BillingAuditAdjustment` (`billing/adjustments`, 27-10) — HasTable adjustment + approve/reject/need-more + issueCreditNote(áp vào hóa đơn) + timeline audit.

**Bẫy `$s`→`$state` (lần 4).** SubscriptionManagement dùng `$s` cho record-param → sed blanket `$s`→`$state` khiến record closure bị Filament resolve state theo TÊN → 500 ("Argument #1 $state must be TenantSubscription, null given"). Bài học: **record closure đặt `$record` (hoặc `$ct`/`$r`/`$a`), chỉ scalar column-value mới `$state`**. 3 màn kia đã dùng `$ct`/`$r`/`$a` nên an toàn.

**Verify:** php -l sạch; view:cache; **9/9 render HTTP 200**; logic `logic_b07.php`: đổi gói/addon/renew, lock usage+sinh 3 alert, sinh 3 hóa đơn, thanh toán một phần, đối soát, ví ±, credit note — 13 dòng billing_audit_logs. Đạt AC subscription/contract/usage/quota/invoice/wallet/adjustment.

**CÒN LẠI:** Round 3 = API `platform/billing/*` (controllers/routes English) + PHPUnit tests (visibility/lifecycle/invoice/wallet/adjustment). Browser-click modal submit chưa test.

---

## 2026-07-01 — Batch 07 SaaS Billing — Round 3: API + tests (HOÀN TẤT)

**API `platform/billing/*` (routes tiếng Anh).** Đăng ký `api:` trong bootstrap/app.php + middleware alias `platform.admin` (`App\Http\Middleware\EnsurePlatformAdmin` — chặn nếu không `isPlatformAdmin`). `routes/api.php` prefix `platform/billing`, 39 route.

**8 controller** (`App\Http\Controllers\Platform\Billing\`, đều dùng trait `WritesBillingAudit`):
- SaasRevenueController@index (MRR/ARR/churn/overage/overdue/top-tenant).
- TenantSubscriptionController (index/show/store + upgrade/downgrade/addAddon/removeAddon/pause/resume/suspend/renew).
- UsageMeteringController (index + recalculate/lock/unlock/generateAlerts).
- QuotaAlertController (index/resolve/convertToAddon/convertToUpgrade).
- BillingInvoiceController (index/show/generate/approve/send/void/recordPayment/reconcile).
- PassThroughWalletController (index/topUp/deduct/configureAutoTopup).
- BillingAdjustmentController (index/approve/reject/issueCreditNote).
- BillingAuditLogController@index.

**Tests:** `tests/Feature/Batch07BillingApiTest.php` (sqlite :memory: + RefreshDatabase, fixtures tối thiểu, actingAs platform admin). **10 test / 39 assertion PASS** phủ 12 flow TEST_SCENARIOS: 403 non-admin, create sub, upgrade→MRR, add-on→MRR, lock usage + gen overage alert, convert alert→addon, generate invoice (line usage_overage) + partial payment→partially_paid, wallet deduct, adjustment approve→credit note (áp vào hóa đơn), suspend→resume. Mỗi flow assert `billing_audit_logs`.

**Lưu ý auth API:** hiện xác thực qua `auth()->user()` (phiên Filament / actingAs trong test). Chưa gắn token Sanctum — nếu cần gọi API stateless từ ngoài, thêm Sanctum sau (localized: đổi middleware group).

**=> BATCH 07 HOÀN TẤT (Round 1 DB + Round 2 9 UI + Round 3 API/tests).** Còn tùy chọn: browser-click modal submit; Sanctum token; proration khi upgrade (hiện đổi MRR, chưa cộng chênh lệch vào hóa đơn kỳ tới).

---

## 2026-07-01 — Lưu context: handoff SuperAdmin + Batch 07

Tạo `docs/SESSION_HANDOFF_20260701_SUPERADMIN_BILLING.md` — snapshot đầy đủ phiên (SuperAdmin WEB-UX-22 12 màn + Batch 07 SaaS Billing 3 rounds): kiến trúc bổ sung (PlatformScreen/WritesBillingAudit/nav groups/FeatureGate reconcile), bảng đối chiếu 21 màn↔page↔slug, cách chạy/verify, 8 bẫy Filament, việc còn lại. Kiểm chứng lại: migrate:fresh --seed sạch, platform+billing render 200, Batch07 API 10/10 test PASS.

---

## 2026-07-17 — BQL-01 cụm Căn hộ + Danh sách cư dân + Chuẩn trang listing

**Chuẩn trang listing** `docs/LISTING_PAGE_STANDARD.md` (áp mọi màn list /admin): header = title ở topbar + breadcrumb (icon, click được) + action ở header Filament (`getHeaderActions`, nút tạo màu `gold`); **KPI card tính lại theo filter**; **X2FilterBar** (inline select + search + drawer nâng cao + chip); **toggle cột** (dropdown "Cột" trong filter bar: `cols` init all-true + `->visible()` + deferred `wire:model` + Áp dụng/Đặt lại, KHÔNG dùng `->toggleable()`); **bulk action inline** (không `BulkActionGroup`); **mobile card** (<768px ẩn `.fi-ta-content`, hiện `.x2-mobile-cards`); **freeze cột** mặc định (ô chọn + `code` sticky trái, thao tác sticky phải; bỏ `->striped()`); bỏ tab điều hướng (dùng sidebar).

**BẪY (đã trả giá):** (1) bảng phải `->query(fn () => $this->filteredQuery())` — **closure**, Builder tĩnh bị Filament cache Table → filter đóng băng; (2) đổi filter phải `resetPage(getTablePaginationPageName())` **+ `flushCachedTableRecords()`** (KHÔNG có `resetTablePage()`); (3) **Filament = v4**: layout `Section/Grid` ở `Filament\Schemas\Components`, action modal dùng `->schema()` (không `->form()`); (4) toggle cột: `cols` init all-true để checkbox khớp cột đang hiện.

**Màn đã dựng:**
- **05 Danh sách căn hộ** (`ApartmentDirectory`) = reference chuẩn listing.
- **06 Chi tiết căn hộ 360** (`ApartmentProfile`, `/apartments/{id}/profile`) bản GIÀU (ref BQL-01-03): KPI strip 7 · 7 section-tab (Thông tin/Cư dân/Xe-thẻ/Công nợ/Phản ánh/Tài liệu/Lịch sử) · tab Thông tin 3 cột (công tơ, cảnh báo, thông tin nhanh). Action thật: Sửa (slide-over form) · Đổi trạng thái (+`apartment_status_histories`) · Tạo ghi chú (append note) · Xuất hồ sơ (CSV) · Phản ánh (FeedbackRequest).
- **07 Cây căn hộ** (`ApartmentTree`) 2 khung cố định scroll dọc riêng: trái cây Dự án→Tòa→Tầng→căn; phải toggle Danh sách/Layout (Layout = upload ảnh mặt bằng + hotspot — **option C, để đợt sau**) + danh sách theo tầng wrap (m²+chủ hộ/"Chưa gắn") + panel chi tiết.
- **01 Danh sách cư dân** (`ResidentDirectory`) theo chuẩn: KPI breakdown trạng thái (Tổng/Hoạt động/Chờ duyệt/Tạm khóa/Thiếu-dữ-liệu=thiếu CCCD) tính theo filter; giữ wizard duyệt hàng loạt.

**Nav:** "Cây căn hộ" thành **mục con của "Hồ sơ căn hộ"** (`navigationParentItem`); nav cha active cả ở màn chi tiết. **Cross-link 2 chiều cư dân ↔ căn hộ** ở mọi màn list/detail/tree.

**Migrations (nullable, không phá seed):** `2026_07_17_000001` (apartments: handover_price/contract_no/contract_signed_at/ownership_term; residents: residence_status) · `000002` (apartments: balcony_direction/position/furniture_status/purpose/contract_type/electric|water|gas_meter_no/documents json). Backfill demo qua `DB::table` (bỏ global scope tenant/project).

**Commits (main, tác giả Joa. Chinh <chtchinh@gmail.com>):** 78d934c → 4292630 → 23740fd → 28838cc → 2e40475 → 1f8fa26 → a235070. Verify: `php -l` + `npm run build` + render 200 + Livewire::test cho filter/tab/toggle/action.

**CÒN LẠI BQL-01:** 04 Chi tiết cư dân 360 (dựng theo `BQL-01-04`, đối xứng chi tiết căn hộ) → 03 wizard thêm → 02 timeline → 08 households/09 residency/10 data-quality. Rồi BQL-02, BQL-03. Layout mặt bằng (option C) làm sau.
