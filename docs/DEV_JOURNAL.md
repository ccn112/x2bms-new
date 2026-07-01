# X2-BMS — Nhật ký phát triển (Dev Journal)

Mỗi lần cập nhật code, ghi một entry vào đầu danh sách (mới nhất ở trên).
Định dạng: ngày · phạm vi · file đổi · tóm tắt · cách verify.

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
