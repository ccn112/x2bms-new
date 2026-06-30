# X2-BMS — Nhật ký phát triển (Dev Journal)

Mỗi lần cập nhật code, ghi một entry vào đầu danh sách (mới nhất ở trên).
Định dạng: ngày · phạm vi · file đổi · tóm tắt · cách verify.

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
