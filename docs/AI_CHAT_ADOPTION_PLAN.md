# Phương án đưa Chat AI (kiểu xweb) sang X2-BMS

> Ngày: 2026-07-18. Nguồn tham chiếu: `xweb/docs/CHAT_MODULE_HANDOFF.md` (+ `apps/cms/src/lib/chat/*`, `packages/chatbot-widget`). Đối chiếu với schema AI sẵn có của `x2/x2web`.

## 1. Tóm tắt quyết định
**Áp dụng KIẾN TRÚC & LOGIC đã kiểm chứng của xweb, viết lại (port) bằng Laravel** — KHÔNG dựng backend Node thứ hai (đúng nguyên tắc "một backend" của X2). x2 đã có **schema lưu trữ AI tốt hơn xweb**; cái thiếu là **lớp service/provider/route streaming + UI widget**. Đây chính là phần ta port từ xweb.

## 2. Đối chiếu

| Khía cạnh | xweb (đang chạy tốt) | x2-bms (hiện trạng) | Kết luận |
|---|---|---|---|
| Trạng thái | ✅ Chạy end-to-end, đã test (Haiku 4.5) | Có **model/DB**, chưa có service/API streaming | Port logic xweb vào x2 |
| Ngôn ngữ/nền | Node/TS, Next, Payload | Laravel/PHP | **Viết lại bằng PHP**, không bê code TS |
| Provider abstraction | ✅ 4 provider (Anthropic/OpenAI/Gemini/Copilot), đổi bằng env | chưa có | **Học nguyên tắc** — 1 interface `streamChat`, chọn qua config |
| Cost control | ✅ rate-limit, cap ngày (anon/registered), log token+cost | có bảng `AiUsageLog` (cost/tokens) nhưng chưa dùng | Port service + dùng bảng sẵn có |
| Guardrail | ✅ `SYSTEM_PROMPT` khóa chủ đề | có `AiGuardrailPolicy`, `AiPromptTemplate` (DB) | **Tốt hơn**: guardrail/prompt cấu hình trong DB, không hardcode |
| RAG | ❌ không có | ✅ `KnowledgeDocument/Chunk/Article`, `AiKnowledgeSource`, `AiRetrievalLog` | x2 vượt trội — có thể thêm RAG cho câu trả lời theo dự án |
| Lưu hội thoại | Payload: `chat-sessions/users/usage` (device UUID) | `AiChatSession/AiChatMessage/AiUsageLog` gắn **User + tenant/project/building** | x2 tốt hơn: danh tính thật + đa tenant |
| Danh tính | device UUID (capability, khách ẩn danh) | **User thật** (Sanctum) + có thể cho public ẩn danh | x2 = quyền chuẩn; giữ chế độ public tuỳ chọn |
| Rate-limit store | in-memory (1 instance) | Redis (đã chuẩn bị ở scale layer) | **Nâng cấp sẵn**: dùng Redis, chạy đa instance |
| Widget UI | React `ChatWidget` (SSE) | chưa có | Web: Livewire/Filament widget; App: màn Flutter |
| Streaming | SSE `text/event-stream` | chưa có | Laravel `response()->stream()` SSE cùng contract |

## 3. Kiến trúc đề xuất cho X2 (giữ ranh giới của xweb)

```
Flutter app  ─┐
Web BQL/admin ─┼─► POST /api/v1/ai/chat (SSE)  ─►  ChatController (ống dẫn, không chứa logic LLM)
Web cư dân   ─┘                                    │
                                                   ▼
                              ChatService (orchestration + guardrail + cost cap)
                                 ├─ LlmProvider (interface streamChat)  ── Anthropic | OpenAI | Gemini
                                 ├─ RagRetriever (dùng KnowledgeChunk/AiRetrievalLog)   [tuỳ chọn]
                                 └─ ChatStore (AiChatSession/Message/UsageLog + Redis rate-limit)
```

Ánh xạ lớp xweb → x2:
- `providers.ts` → **`App\Services\Ai\Providers\*`** (interface `LlmProvider::stream()`), chọn provider qua `config/ai.php` + env. Bê nguyên **nguyên tắc** (async delta + usage cuối stream, xử lý ảnh/PDF theo provider).
- `service.ts` → **`App\Services\Ai\ChatService`**: rate-limit (Redis), cap ngày (User/anon), cắt lịch sử, ghép guardrail (từ `AiGuardrailPolicy`/`AiPromptTemplate`), stream, lưu usage (best-effort). Hằng số cost đưa vào `config/ai.php` (env-overridable).
- `store.ts` → **`App\Services\Ai\ChatStore`** trên các bảng `Ai*` sẵn có (không tạo bảng mới; bổ sung cột nếu thiếu: token/cost cộng dồn, siteCode, lastRoute).
- Route SSE → **`/api/v1/ai/chat`** (Sanctum optional cho public) + `/ai/chat/sessions`, giữ **contract giống xweb** (`{type:delta|done|error}`) để widget/app tái dùng dễ.
- Widget → **Web**: Filament/Livewire floating widget (góc màn); **App**: màn Flutter `X2AI` (đi qua Repository + cache local lịch sử).

## 4. Điểm x2 làm TỐT HƠN xweb (khai thác)
1. **RAG theo ngữ cảnh dự án**: trả lời dựa `KnowledgeChunk` của đúng tenant/project (nội quy, phí, tiện ích) — xweb không có.
2. **Guardrail/prompt trong DB** (`AiGuardrailPolicy`, `AiPromptTemplate`): admin đổi không cần deploy (điều xweb liệt kê là "nâng cấp chưa làm").
3. **Danh tính & đa tenant thật**: hội thoại gắn User + scope, phân quyền chuẩn thay vì device UUID.
4. **Redis sẵn sàng**: rate-limit/cap phân tán đa instance (xweb đang in-memory).
5. **Usage/cost + governance** (`AiUsageLog`, `AiRequest`, `AiTestRun`): theo dõi chi phí theo tenant/project.

## 5. Điểm cần bê nguyên tinh thần từ xweb
- **Key chỉ ở backend**, client là ống dẫn (app/web không chạm key).
- **Cost control mặc định bật**: `MAX_OUTPUT_TOKENS`, `MAX_HISTORY_MESSAGES`, `MAX_INPUT_CHARS`, cap ngày, `MAX_IMAGES/MAX_FILE_BYTES`.
- **Đổi provider không sửa code** (env/DB config).
- **SSE delta + usage cuối stream**; lỗi tiền điều kiện trả JSON+status (429/403/413) với `code`.
- **`estCost` là ước tính**, đối chiếu định kỳ.

## 6. Lộ trình
1. **Slice A — Lõi backend** (`config/ai.php`, `LlmProvider` Anthropic + `ChatService` + `ChatStore` trên bảng Ai* + route `/api/v1/ai/chat` SSE + rate-limit Redis + guardrail từ DB). Nghiệm thu: stream Haiku, cap hoạt động, lưu `AiChatSession/Message/UsageLog`.
2. **Slice B — Web widget** floating (Livewire) trên panel BQL/admin, dùng contract SSE.
3. **Slice C — App Flutter** màn X2AI (Repository + cache lịch sử local, offline hiển thị hội thoại cũ).
4. **Slice D — RAG** nối `KnowledgeChunk` theo project + `AiRetrievalLog`.
5. **Slice E — provider thứ 2** (OpenAI/Gemini) + governance/test (`AiTestRun`).

## 6b. Slice A — ĐÃ LÀM & verify (2026-07-18)
Chốt: **Anthropic Haiku 4.5**; app cho **chat ẩn danh** (chỉ tác vụ cơ bản), tác vụ nâng cao đẩy ra Action Gate đăng nhập; web tự động định danh. RAG để Slice D. Cap ngày: anon 12 / đăng nhập 60 (config).

Đã hiện thực (Laravel, không dựng Node):
- `config/ai.php` (provider/model/limits/prices/guardrail + `CHAT_PROVIDER` env; có provider **`fake`** để test local không cần key).
- `App\Services\Ai\`: `LlmProvider` (interface) + `AnthropicProvider` (SSE streaming) + `FakeProvider`; `AiProviderFactory`; `ChatService` (`preflight()` kiểm rate-limit/cap/gating → JSON+status; `streamAnswer()` stream + lưu); `ChatStore` (trên bảng Ai* sẵn có); `GuardrailResolver`; `ChatException`.
- Migration `ai_chat_anonymous_and_rollup`: `ai_chat_sessions/messages.user_id` nullable + `device_id` + rollup token/cost.
- Route SSE `POST /api/v1/ai/chat` + `GET /ai/chat/sessions[/{id}]` (auth optional). Contract `data:{type:delta|done|error}` giống xweb.
- **Verify (fake provider)**: stream deltas → `done` với session_id; hội thoại lưu (message_count=2), usage/cost ghi vào `ai_usage_logs`; sessions list/resume theo device.

⚠️ Local đặt `CHAT_PROVIDER=fake` trong `.env` (chưa có key). Sản xuất: `CHAT_PROVIDER=anthropic` + `ANTHROPIC_API_KEY`.
Còn lại: Slice B (web Livewire widget) · C (Flutter X2AI screen) · D (RAG) · E (guardrail/prompt từ DB + provider 2 + governance).

## 7. Quyết định cần chốt
- Provider mặc định + model (đề xuất **Anthropic Haiku 4.5** cho chi phí, như xweb).
- Có bật **chat ẩn danh (public)** trong app không, hay chỉ sau đăng nhập.
- Bật **RAG** ngay Slice A hay để Slice D.
- Ngân sách cap ngày cho cư dân/BQL.

> Ghi chú: KHÔNG copy code TS của xweb vào x2. Chỉ tái dùng **thiết kế, contract SSE, danh sách hằng số kiểm soát chi phí, và guardrail approach**. Storage dùng schema Ai* sẵn có của x2.
