# X2-BMS — Session Handoff (2026-07-01)

Bàn giao phiên làm việc 2026-07-01. Phiên này tập trung **đại tu module X2AI Copilot (WEB-UX-09)** và
tinh chỉnh sidebar. Đọc kèm `docs/DEV_JOURNAL.md` (nhật ký thay đổi chi tiết, mới nhất ở trên).

## 0. Đọc trước (reading order cho phiên sau)

1. **`docs/DEV_JOURNAL.md`** — nhật ký mọi lần đổi code (nguồn chính, append mỗi lần sửa).
2. `docs/SESSION_HANDOFF_20260701.md` (file này) + `docs/SESSION_HANDOFF_20260630.md`.
3. `docs/CANONICAL_ENTITY_MAP.md`, `docs/WEB_FORM_RECONCILIATION.md`, `docs/SESSION_CONTEXT.md`.
4. Memory tự nạp: `x2bms-dev-journal-rule` (luật ghi nhật ký), `x2-bms-backend-runbook`, `x2-bms-build-decisions`.

> **Quy ước (chủ dự án chốt):** mỗi lần đổi code ⇒ append 1 entry vào `docs/DEV_JOURNAL.md`
> (ngày · phạm vi · file · tóm tắt · cách verify) trước khi báo cáo.

## 1. Phiên này đã làm (tóm tắt — chi tiết xem DEV_JOURNAL)

Toàn bộ xoay quanh **X2AI Copilot** (khung chat nổi toàn cục `/admin`, WEB-UX-09):

- **Audit thật**: mỗi lượt chat ghi `ai_usage_logs` (tokens/model/latency/cost VND/risk/status) →
  màn Governance/Center phản ánh usage thật, không chỉ seed. (`X2aiClient` thu telemetry; `X2aiChat::logUsage`.)
- **Governance gate** (`app/Support/X2AI/X2aiPolicyGate.php`): mode theo **permission** (`ai.use`,
  `ai.data_lookup` — seed trong `DemoDataSeeder`), không còn toggle UI; risk + human-approval đọc từ
  `ai_policies` active; chính sách active được nhồi vào system prompt.
- **UX chat kiểu ChatGPT**: gửi 2 bước (`submit()` hiện prompt ngay → `generate()` chờ kết quả, kích bằng
  `x-init="$wire.generate()"`); render Markdown→HTML an toàn (GFM, tables) qua `GithubFlavoredMarkdownConverter`;
  tự cuộn xuống đáy (event `x2ai-scroll`).
- **Lịch sử theo PHIÊN**: bảng `ai_chat_sessions` + `ai_chat_messages.ai_chat_session_id`. Mỗi lần mở
  trang = phiên mới (tạo lazy, title = prompt đầu, surface = màn hình). Nút **Lịch sử** + **Cuộc trò chuyện mới**
  nằm ở **header** (gọi qua `Livewire.dispatch` → `#[On(...)]`).
- **Bố cục khung chat**: cao `66vh` (mặc định) / `w-[50vw] h-66vh` (mở rộng) — chốt bằng **inline-style**
  (không phụ thuộc Tailwind build); ô **input ở đáy**, vùng dữ liệu cuộn ở trên (max-height inline).
- **Fix upload file**: php.ini Herd `upload_max_filesize` 2M→20M, `post_max_size` 8M→25M
  (`C:\Users\chtch\.config\herd\bin\php84\php.ini`). **Cần restart serve/Herd.**
- **Sidebar**: bỏ user-card ở chân (gỡ hook `SIDEBAR_FOOTER`), ẩn thanh scrollbar (`.fi-sidebar(-nav)`).

### Bảng/model/migration mới trong phiên
- migrations: `..._000011_create_ai_engine_tables` (từ trước), `..._000012_create_ai_chat_messages`,
  `..._000013_create_ai_chat_sessions`.
- models: `AiChatMessage`, `AiChatSession` (+ `AiPolicy`/`AiUsageLog`… từ AI Engine).
- service: `X2aiClient`, `X2aiDataConnector`, `X2aiPolicyGate` (trong `app/Support/X2AI/`).
- UI: `app/Livewire/X2aiChat.php`, `resources/views/livewire/x2ai-chat.blade.php`,
  `resources/views/components/x2/ai-fab.blade.php`.

## 2. Trạng thái X2AI Copilot (as-built)

- 1 khung chat nổi duy nhất, mount global qua `renderHook(PANELS_BODY_END)` ở `AdminPanelProvider`.
- Header: `[+ mới] [🕘 lịch sử] [⤢ phóng to] [✕]`. Thân cuộn. Input đáy + đính kèm ảnh/PDF (vision).
- Mode `context` mặc định; mode `data` (tool `lookup_data`) chỉ bật khi có `X2AI_DATA_API_URL`
  **và** user có `ai.data_lookup`. Hiện chưa có data API → luôn context.
- Anthropic Messages API qua `config('services.x2ai')` (`X2AI_API_KEY`, `X2AI_MODEL=claude-haiku-4-5`).

## 3. Còn lại / việc tiếp theo

1. **🔴 BẢO MẬT (mục 1, CHƯA làm)**: `X2AI_API_KEY` thật bị commit trong `.env.example` — cần rotate key
   trên Anthropic Console + thay placeholder + cân nhắc xóa khỏi git history.
2. **X2AI Mode 2**: khi có `X2AI_DATA_API_URL`, map `X2aiDataConnector::query()` theo shape thật + test.
3. **X2AI client-side actions** (navigate/click/fill) — AI hiện mới ĐỌC màn hình (chủ dự án quan tâm).
4. **Markdown**: hiện strip HTML thô (chỉ render Markdown) — nếu cần render HTML mô hình trả về thì cân nhắc sanitizer.
5. **Roadmap build chính** (từ SESSION_HANDOFF_20260630 §6): màn **Công nợ & Thanh toán (WEB-FORM-08)**
   bespoke `/admin` (data đã có); form fee/billing còn thiếu; **layer API mobile (M3/M4) chưa bắt đầu**
   (chưa có `routes/api.php`/controller).

## 4. Chạy & verify

- Reseed: `php artisan migrate:fresh --seed` · Migrate thêm: `php artisan migrate`.
- Build (Node 22): `npm run build` · Views: `php artisan view:cache` / `view:clear`.
- Serve: `php artisan serve` (PHP_CLI_SERVER_WORKERS đã set). **Restart sau khi đổi php.ini.**
- Login admin: `x2bms@x2bms.vn` / `Bms@2026!` (super_admin). Resident demo: `nguyenvananh@gmail.com` / `Resident@2026!`.
- Toolchain gotchas: xem memory `x2-bms-backend-runbook` (Node 22, Herd composer/php.bat, upload limits).
