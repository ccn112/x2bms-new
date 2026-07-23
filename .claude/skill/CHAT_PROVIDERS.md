# AI Chat — Đa nhà cung cấp (providers) & CLI

Chatbot "Trợ lý XTECH" hỗ trợ 4 nhà cung cấp LLM. **Toàn bộ logic gọi LLM + API key nằm ở backend CMS** (`apps/cms`); website (`apps/clay`) chỉ **proxy** request sang CMS và pipe luồng SSE về trình duyệt. Nhờ vậy key không bao giờ nằm ở tầng frontend. Lưu lượng, token và **chi phí tạm tính** được ghi vào CMS (nhóm **Chat**).

> Kiến trúc chi tiết + hướng dẫn tái dùng module cho phần mềm khác: **`docs/CHAT_MODULE_HANDOFF.md`**.

## 1. Chọn provider (env — root `.env`, do CMS nạp)

Cấu hình đặt ở **`.env` gốc của workspace** (payload.config nạp file này). **Không** đặt key ở `apps/clay`.

```dotenv
CHAT_PROVIDER=anthropic        # anthropic | openai | gemini | copilot

# Anthropic (Claude) — mặc định, rẻ nhất cho web public
ANTHROPIC_API_KEY=sk-ant-...
ANTHROPIC_MODEL=claude-haiku-4-5

# OpenAI
# OPENAI_API_KEY=sk-...
OPENAI_MODEL=gpt-4o-mini

# Google Gemini
# GEMINI_API_KEY=...            # hoặc GOOGLE_API_KEY
GEMINI_MODEL=gemini-2.5-flash

# GitHub Copilot / GitHub Models (endpoint tương thích OpenAI)
# COPILOT_TOKEN=github_pat_...   # hoặc GITHUB_TOKEN
COPILOT_MODEL=openai/gpt-4o-mini
COPILOT_BASE_URL=https://models.github.ai/inference

# Giới hạn (chống đốt token) — thực thi ở CMS
CHAT_RATE_LIMIT_PER_MINUTE=20
CHAT_ANON_DAILY_MAX=12          # ẩn danh
CHAT_REG_DAILY_MAX=60          # đã đăng ký
```

Đổi provider = đổi `CHAT_PROVIDER` (+ đảm bảo key tương ứng) rồi **khởi động lại CMS**. Không cần sửa code, không đụng tới clay.

`apps/clay/.env.local` giờ chỉ cần biết CMS ở đâu:

```dotenv
CMS_URL=http://localhost:3000
NEXT_PUBLIC_CMS_URL=http://localhost:3000
```

> ⚠️ `.env` gốc đã `.gitignore` — **không commit key**. Khi deploy, set các biến này ở môi trường prod của **CMS**.

## 2. Chi phí & usage trong CMS

- **Chat → Chat Usage**: mỗi ngày × provider × model → số request, token vào/ra, **chi phí tạm tính (USD)**.
- **Chat → Chat Sessions**: từng phiên có `tokensIn/tokensOut/estCostUsd` tích lũy + `flaggedQuality` (đánh dấu hội thoại hay để làm bài viết).
- **Chat → Chat Users**: email + SĐT người dùng đã đăng ký (cũng là lead).

Bảng giá tạm tính ($/1M token) nằm ở `apps/cms/src/lib/chat/providers.ts` (`PRICES`) — cập nhật khi giá đổi.

## 3. CLI dev cho từng công cụ

Đã cài (npm global): `codex` (OpenAI), `gemini` (Google). `claude` (Claude Code) và `copilot` (GitHub) đã có sẵn.

| Công cụ | CLI | Đăng nhập (chạy trong terminal của bạn) |
|---|---|---|
| Anthropic | `claude` (Claude Code) | Đã đăng nhập. API CLI `ant`: tải binary từ github.com/anthropics/anthropic-cli/releases rồi `ant auth login` (tùy chọn) |
| OpenAI | `codex` | `codex login` (đăng nhập OpenAI) hoặc đặt `OPENAI_API_KEY` |
| Google | `gemini` | Chạy `gemini` lần đầu → đăng nhập Google, hoặc đặt `GEMINI_API_KEY` |
| GitHub Copilot | `copilot` | Chạy `copilot` → đăng nhập GitHub (cần gói Copilot) |

> Đăng nhập là thao tác tương tác — hãy tự chạy trong terminal (hoặc gõ `! <lệnh>` trong phiên này). Sau khi đăng nhập, các CLI dùng để thử nghiệm / prototyping với từng provider.

## 4. Kiến trúc code (tóm tắt)

**CMS (`apps/cms`) — chủ sở hữu module:**
- `src/lib/chat/providers.ts` — `streamChat({provider, model, system, messages, attachments})` → `{ text: AsyncGenerator, usage: Promise }`. 4 impl: Anthropic SDK, OpenAI SDK, `@google/genai`, OpenAI SDK trỏ `COPILOT_BASE_URL` cho Copilot. Thuần Node, không phụ thuộc framework.
- `src/lib/chat/store.ts` — lưu session/user/usage qua **Payload Local API** (không HTTP nội bộ).
- `src/lib/chat/service.ts` — guardrail chủ đề, rate-limit, daily cap, gating đăng ký, orchestration + persistence.
- `src/app/(chat)/api/chat/route.ts` (+ `register`, `sessions`) — endpoint SSE.

**clay (`apps/clay`) — frontend mỏng:**
- `src/app/api/chat/route.ts` (+ `register`, `sessions`) — **proxy** sang CMS, pipe SSE.
- `src/components/chat/ChatWidget.tsx` — UI (FAB + popup, markdown, lịch sử, đăng ký).

**Đã verify** (2026-07-18): clay proxy → CMS → Anthropic (Haiku 4.5): stream, guardrail off-topic, đăng ký (403 gating), lịch sử, và chi phí lưu vào `chat-sessions` + `chat-usage`. 3 provider kia đã wire code, cần điền key để test.
