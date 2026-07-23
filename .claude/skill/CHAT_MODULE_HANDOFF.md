# AI Chat Module — Handoff & Tái sử dụng

Tài liệu này mô tả **logic, kiến trúc và cách xử lý** của module chat AI đã xây trong dự án XTECH, để bạn bê nguyên (hoặc cắt gọt) sang các **phần mềm SaaS và sản phẩm khác**.

Mục tiêu thiết kế:
- **Key + logic gọi LLM ở backend**, frontend không bao giờ chạm key.
- **Đổi nhà cung cấp không sửa code** (Anthropic / OpenAI / Gemini / Copilot).
- **Kiểm soát chi phí**: giới hạn token, rate-limit, hạn mức ngày, ghi lại token + chi phí.
- **Tách lớp rõ ràng** để phần "gọi LLM" tái dùng ở bất kỳ backend Node nào; chỉ lớp lưu trữ là gắn với CMS.

---

## 1. Kiến trúc & luồng dữ liệu

```
┌────────────┐   POST /api/chat        ┌──────────────────────┐   SDK/HTTPS   ┌────────────┐
│  Browser   │  (JSON, SSE response)   │   Frontend (clay)    │   proxy       │            │
│ ChatWidget │ ──────────────────────► │  app/api/chat/route  │ ────────────► │            │
│            │ ◄────── SSE stream ───── │  (pipe stream, NO    │ ◄──────────── │            │
└────────────┘                         │   key, NO LLM logic) │               │            │
                                       └──────────────────────┘               │  CMS       │
                                                                              │ backend    │
                                        ┌──────────────────────┐   SDK        │            │
                                        │   CMS (apps/cms)     │ ───────────► │ providers  │──► LLM API
                                        │  (chat)/api/chat     │              │            │   (Claude/…)
                                        │   service → store    │              └────────────┘
                                        │   Payload Local API  │
                                        └──────────┬───────────┘
                                                   │ (no internal HTTP)
                                          ┌────────▼─────────┐
                                          │  Postgres (CMS)  │  chat-sessions / chat-users / chat-usage
                                          └──────────────────┘
```

**Nguyên tắc:** frontend chỉ là ống dẫn. Mọi quyết định (provider nào, model nào, có chặn không, ghi chi phí) đều ở CMS. Nếu mai có thêm app mobile / landing khác, chúng dùng chung 1 chat-service này → giới hạn & chi phí kiểm soát tại **một** chỗ.

Vì sao proxy qua frontend thay vì gọi thẳng CMS từ browser? Để giữ **same-origin** (không cần CORS, không lộ URL nội bộ của CMS ra client). Frontend chỉ `fetch` sang CMS rồi trả `response.body` nguyên vẹn.

---

## 2. Các lớp code & trách nhiệm

| Lớp | File (trong `apps/cms`) | Phụ thuộc | Tái dùng |
|---|---|---|---|
| **Providers** | `src/lib/chat/providers.ts` | chỉ SDK LLM | ✅ Bê nguyên sang mọi backend Node |
| **Store** | `src/lib/chat/store.ts` | Payload Local API | 🔁 Thay lớp này để đổi CSDL/backend |
| **Service** | `src/lib/chat/service.ts` | providers + store | ✅ Logic thuần, đổi guardrail/limit tại đây |
| **Routes** | `src/app/(chat)/api/chat/*` | Next route handler | 🔁 Đổi theo framework (Express/Fastify…) |
| **Client helper** | `src/lib/payload-client.ts` | Payload | 🔁 Chỉ cần khi dùng Payload |
| **UI** | `apps/clay/src/components/chat/ChatWidget.tsx` | React | 🔁 Tùy frontend |
| **Proxy** | `apps/clay/src/app/api/chat/*` | Next route handler | 🔁 Tùy frontend |

Ranh giới quan trọng: **providers.ts** và phần lớn **service.ts** không biết gì về Payload/Next → đây là "lõi" tái dùng. **store.ts** là chỗ duy nhất chạm CSDL.

---

## 3. Lớp Providers — trừu tượng đa nhà cung cấp

Một interface streaming chung cho cả 4 provider:

```ts
type ChatMsg = { role: 'user' | 'assistant'; content: string }
type ChatAttachment = { kind: 'image' | 'pdf'; mediaType: string; data: string } // base64, no prefix
type ChatUsage = { inputTokens: number; outputTokens: number }
type ChatStream = { text: AsyncGenerator<string>; usage: Promise<ChatUsage> }

streamChat({ provider, model, system, messages, attachments, maxTokens }): Promise<ChatStream>
```

- `text` là async generator phát từng **delta** văn bản → stream ra người dùng ngay (độ trễ thấp, không chờ hết câu).
- `usage` là Promise resolve **sau khi** stream xong, mang token in/out để tính chi phí.
- Provider chọn qua env `CHAT_PROVIDER`; model qua `<PROVIDER>_MODEL`.

Điểm khác biệt từng provider (đã xử lý sẵn bên trong):
- **Anthropic**: `messages.stream`; ảnh = block `image`, PDF = block `document`; bỏ field `thinking` cho Haiku (Haiku tắt thinking mặc định), chỉ gửi `thinking:{type:'disabled'}` cho model đời cao hơn.
- **OpenAI / Copilot**: `chat.completions.create` với `stream_options.include_usage`. Copilot = OpenAI SDK trỏ `baseURL = COPILOT_BASE_URL` (GitHub Models) + GitHub token.
- **Gemini**: `generateContentStream`, system đi vào `config.systemInstruction`, ảnh/PDF = `inlineData`.

**Thêm provider mới:** viết 1 hàm `xxxStream(...)` trả `ChatStream`, thêm nhánh `case` trong `streamChat`, thêm giá vào `PRICES`. Không đụng service/route.

**Chi phí:** `estimateCost(model, usage)` tra bảng `PRICES` ($/1M token). Đây là *ước tính* (không phải hóa đơn thật) để theo dõi nhanh; cập nhật bảng khi giá đổi.

---

## 4. Lớp Service — orchestration & kiểm soát

`runChat(input)` là trái tim. Trình tự:

1. **Rate-limit** theo `deviceId` (mặc định 20 req/phút) → 429 nếu vượt.
2. **Kiểm tra đăng ký** (`isRegistered`): file/ảnh đính kèm yêu cầu đã đăng ký, nếu chưa → 403 `register_required`.
3. **Hạn mức ngày**: ẩn danh 12, đã đăng ký 60 (config được) → 429 `register_for_more` / `daily_limit`.
4. **Nạp lịch sử** phiên từ store, **cắt** còn `MAX_HISTORY_MESSAGES` (14) turn gần nhất để tiết kiệm token.
5. Ghép `system` = **guardrail prompt** + ngữ cảnh trang hiện tại.
6. Gọi `streamChat`, đẩy delta ra SSE.
7. **Sau khi xong**: lưu hội thoại (cộng dồn token/chi phí vào phiên) + cập nhật rollup ngày. Đây là *best-effort* (lỗi lưu không làm hỏng câu trả lời đã stream).

Các hằng số kiểm soát chi phí (đầu file `service.ts`):

| Hằng | Mặc định | Ý nghĩa |
|---|---|---|
| `MAX_INPUT_CHARS` | 4000 | Chặn prompt quá dài |
| `MAX_HISTORY_MESSAGES` | 14 | Số turn lịch sử gửi cho model |
| `MAX_OUTPUT_TOKENS` | 1024 | Trần token đầu ra mỗi câu |
| `MAX_IMAGES` | 3 | Số ảnh tối đa/lượt |
| `MAX_FILE_BYTES` | 5MB | Trần dung lượng mỗi tệp |

**Guardrail** là `SYSTEM_PROMPT`: khóa chủ đề (chuyển đổi số / AI / bất động sản), từ chối off-topic, cấm bảng markdown, chèn CTA, khuyến khích đóng góp nội dung. Đổi lĩnh vực SaaS của bạn = viết lại prompt này (và có thể danh sách CTA).

---

## 5. Lớp Store — lưu trữ (điểm thay khi tái dùng)

Dùng **Payload Local API** (gọi hàm trực tiếp, không HTTP nội bộ) → nhanh + không phải mở REST công khai cho ghi.

Hàm public: `isRegistered`, `upsertUser`, `loadSession`, `saveSession`, `listSessions`, `getSessionForDevice`, `hideSessionForDevice`, `recordUsage`.

**Để tái dùng trên backend khác** (Prisma/Drizzle/Mongo…): chỉ cần viết lại `store.ts` giữ nguyên chữ ký hàm. Service không đổi.

### Data model (3 bảng)

- **chat-sessions** — 1 dòng/phiên (khóa `sessionId`, gắn `deviceId`):
  `messages` (JSON: `{role, content, images?, ts}[]`), `messageCount`, `provider`, `model`, `tokensIn`, `tokensOut`, `estCostUsd` (cộng dồn), `siteCode`, `lastRoute`, `hiddenByUser` (xóa mềm phía người dùng), `flaggedQuality` (nhân viên đánh dấu hội thoại hay).
- **chat-users** — người đã đăng ký (khóa `deviceId`): `email`, `phone`, `name`, `siteCode`. Đây cũng là **lead**.
- **chat-usage** — rollup ngày (khóa `key = day:provider:model`): `requests`, `tokensIn`, `tokensOut`, `estCostUsd`.

**Kiểm soát truy cập** theo kiểu *capability*: `deviceId` là UUID ngẫu nhiên lưu ở `localStorage`, đóng vai "khóa" cho dữ liệu của thiết bị đó. Không có tài khoản/mật khẩu cho khách. Truy cập nội bộ (Local API) bỏ qua access-control; REST công khai chỉ để đọc.

---

## 6. Hợp đồng API (contract)

### `POST /api/chat` → SSE
Request JSON:
```json
{ "deviceId": "uuid", "sessionId": "uuid", "message": "…",
  "siteCode": "corporate", "route": "/san-pham", "pageContext": "…",
  "attachments": [{ "kind": "image", "mediaType": "image/png", "data": "<base64>" }] }
```
Response: `text/event-stream`, mỗi dòng `data: {json}\n\n`:
- `{"type":"delta","text":"…"}` — mảnh văn bản (nối lại để hiển thị).
- `{"type":"done"}` — kết thúc.
- `{"type":"error","message":"…"}` — lỗi khi sinh câu trả lời.

Lỗi tiền điều kiện (rate-limit, cap, gating, validate) trả **JSON thường** kèm HTTP status (400/403/413/429) + `code` (`register_required` | `register_for_more` | `daily_limit`), không phải SSE.

### `POST /api/chat/register`
`{ deviceId, email, phone, name?, siteCode }` → `{ ok: true }` (validate email + SĐT).

### `GET /api/chat/sessions?deviceId=[&sessionId=]`
Đã đăng ký mới gọi được (nếu không → 403). Không `sessionId`: liệt kê phiên. Có `sessionId`: trả `messages` để resume.

### `DELETE /api/chat/sessions?deviceId=&sessionId=`
Xóa mềm (`hiddenByUser=true`) — nhân viên vẫn giữ để khai thác nội dung.

---

## 7. Cấu hình (env)

Đặt ở env của **backend gọi LLM** (ở đây là `.env` gốc do CMS nạp). Xem `docs/CHAT_PROVIDERS.md` để biết danh sách đầy đủ. Tối thiểu:
```dotenv
CHAT_PROVIDER=anthropic
ANTHROPIC_API_KEY=sk-ant-...
ANTHROPIC_MODEL=claude-haiku-4-5   # rẻ nhất, hợp cho web public
CHAT_RATE_LIMIT_PER_MINUTE=20
CHAT_ANON_DAILY_MAX=12
CHAT_REG_DAILY_MAX=60
```

---

## 8. Checklist tái dùng cho SaaS khác

1. **Copy** `src/lib/chat/providers.ts` (không đổi) + `service.ts`.
2. **Viết lại** `store.ts` theo CSDL của bạn (giữ chữ ký hàm) — hoặc dùng Payload thì bê nguyên.
3. **Đổi `SYSTEM_PROMPT`** sang lĩnh vực/chính sách của bạn; chỉnh danh sách CTA & link.
4. **Gắn route** theo framework (Next route handler / Express / Fastify) — chỉ cần gọi `parseChatInput` → `runChat` → trả stream.
5. **Frontend**: bê `ChatWidget.tsx` hoặc tự viết; nhớ tạo `deviceId` (UUID) lưu `localStorage`, gửi kèm mỗi request; parse SSE.
6. **Điền key** provider muốn dùng, set `CHAT_PROVIDER`.
7. **Chỉnh hằng số chi phí** (`MAX_OUTPUT_TOKENS`, cap ngày…) theo ngân sách.

---

## 9. Bảo mật & lưu ý vận hành

- **Key chỉ ở backend.** Không đưa vào biến `NEXT_PUBLIC_*`, không để frontend gọi thẳng LLM.
- **`deviceId` là capability, không phải danh tính** — đừng dùng nó để phân quyền dữ liệu nhạy cảm thật; nó chỉ tách lịch sử chat theo trình duyệt. Với SaaS có đăng nhập, thay bằng userId thật + access-control chuẩn.
- **Đính kèm là base64 trong JSON** → tốn RAM; đã chặn 5MB/tệp. Tải lớn nên chuyển sang upload S3 rồi truyền URL.
- **Rate-limit & cap hiện lưu in-memory trong tiến trình CMS** → đúng khi chạy 1 instance. Chạy nhiều instance/serverless: chuyển sang **Redis** (hoặc bảng đếm trong DB) để chia sẻ trạng thái. Đây là điểm nâng cấp đã biết.
- **`estCostUsd` là ước tính**, không phải hóa đơn thật — dùng để cảnh báo/theo dõi, đối chiếu định kỳ với dashboard của nhà cung cấp.
- **PII**: `chat-users` chứa email/SĐT (lead) và `chat-sessions` chứa nội dung người dùng nhập → áp chính sách lưu trữ/xóa theo luật (xem trang pháp lý của dự án).

---

## 10. Nâng cấp gợi ý (chưa làm)

- **Global "Chat Settings" trong CMS**: cho admin đổi provider/model/cap **không cần deploy** (config phi bí mật lưu DB, key vẫn ở env).
- **Redis** cho rate-limit/cap phân tán.
- **Upload S3** cho tệp lớn thay vì base64.
- **Streaming usage chính xác hơn** với provider trả token giữa chừng; hiện chốt ở cuối stream.

---

*Đã kiểm thử end-to-end (2026-07-18): clay proxy → CMS → Anthropic Haiku 4.5 — stream, guardrail off-topic, đăng ký (403 gating), lịch sử, chi phí lưu vào `chat-sessions` + `chat-usage`.*
