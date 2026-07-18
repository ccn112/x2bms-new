# API_RESPONSE_AND_ERROR_STANDARD — X2-BMS (Audit 2026-07-18)

> Hai phần: **A. Quy ước ĐANG DÙNG** (rút từ code thực tế) và **B. Chuẩn KHUYẾN NGHỊ** cho tầng Mobile API mới. Phần B là *đề xuất*, bám theo hành vi hiện có của codebase.

---

## PHẦN A — Quy ước ĐANG DÙNG (thực tế trong code)

Nguồn: `app/Http/Controllers/Platform/**`, `bootstrap/app.php`, `config/app.php`, `routes/*`, `lang/`.

### A.1. Envelope thành công — **KHÔNG có**
Không có wrapper thống nhất. Controller trả trực tiếp một trong các dạng:

- **Model Eloquent thô** (kèm relation đã load), ví dụ `TenantSubscriptionController@show`:
  ```
  return response()->json($subscription->load([...]));   // → JSON các field model, không bọc "data"
  ```
- **Paginator Laravel** (list endpoints), ví dụ `@index`:
  ```
  return response()->json($query->paginate((int) $request->get('per_page', 20)));
  ```
  → Trả cấu trúc paginator mặc định của Laravel: `{data:[...], current_page, first_page_url, from, last_page, last_page_url, links:[...], next_page_url, path, per_page, prev_page_url, to, total}`. (Đây là chỗ **duy nhất** có `data` — do paginator, không phải quy ước của team.)
- **Mảng ad-hoc**, ví dụ:
  - `SaasRevenueController@index` → `{mrr, arr, active_subscriptions, ...}`
  - `TenantSubscriptionController@removeAddon` → `{ok: true}`
  - `IntegrationConnectionController@test` → `{result, latency_ms, http_status}`
  - `rotateSecret` → `{secret, masked}` (secret trả 1 lần duy nhất)
  - `BillingInvoiceController@generate` → `{created: <int>}`

### A.2. HTTP status khi thành công
- Tạo mới (`store`, `addAddon`, `addMessage`...) → **201** (tham số thứ hai của `response()->json($model, 201)`).
- Còn lại → **200** mặc định.
- **Không** có 204 No Content (kể cả xóa addon vẫn trả `{ok:true}` + 200).

### A.3. Lỗi validation — Laravel mặc định (422)
Controller dùng `$request->validate([...])`. Vì `bootstrap/app.php` khai báo `shouldRenderJsonWhen($request->is('api/*'))`, lỗi validate dưới `/api/*` render JSON chuẩn Laravel:
```json
{
  "message": "The tenant id field is required. (and 1 more error)",
  "errors": {
    "tenant_id": ["The tenant id field is required."],
    "plan_id":   ["The selected plan id is invalid."]
  }
}
```
HTTP **422**. Thông báo mặc định bằng **tiếng Anh** (do `config('app.locale') = 'en'`, xem A.7).

### A.4. Lỗi ủy quyền — 403 từ EnsurePlatformAdmin
`App\Http\Middleware\EnsurePlatformAdmin` gọi `abort(403, 'Chỉ SuperAdmin/Billing admin được truy cập API billing.')`. Dưới `/api/*` render JSON:
```json
{ "message": "Chỉ SuperAdmin/Billing admin được truy cập API billing." }
```
HTTP **403**. Không có mã lỗi máy đọc (`code`), không phân biệt "chưa đăng nhập" (401) với "không đủ quyền" (403) — cả hai đều 403 (vì middleware kiểm `!$user || !isPlatformAdmin()` chung một nhánh). **Không có 401 và không có luồng token.**

### A.5. Lỗi nghiệp vụ (business error)
Xử lý rời rạc, không thống nhất:
- `BillingInvoiceController@generate` khi kỳ usage chưa khóa → `return response()->json(['message' => 'Ky usage chua khoa'], 422);` (message tiếng Việt **không dấu**, ad-hoc).
- `ResetsResidentPassword` (Filament) khi cư dân chưa có tài khoản → hiện **Filament Notification**, không phải phản hồi API.
- Không có mã lỗi nghiệp vụ (error code enum), không có catalog lỗi.

### A.6. Lỗi 404 & 500
- Route model binding không tìm thấy → `ModelNotFoundException` → **404** JSON `{"message": "..."}` (Laravel mặc định, dưới `/api/*`).
- Exception khác → **500**; chi tiết stack chỉ hiện khi `APP_DEBUG=true` (mặc định `false` ⇒ `{"message":"Server Error"}`).

### A.7. Phân trang (pagination)
- API dùng `->paginate((int) $request->get('per_page', <mặc định>))`. Mặc định `per_page`: subscriptions/invoices/connections = **20**, tickets = **25**. Client truyền `?per_page=`.
- Meta phân trang = **format paginator mặc định của Laravel** (xem A.1), không tùy biến, không bọc `meta` riêng.
- Filament table (UI) phân trang server-side độc lập, không liên quan API.

### A.8. Ngày/giờ & timezone
- `config/app.php` → `'timezone' => 'UTC'`. Toàn app chạy giờ **UTC**.
- Controller dùng `now()`, `->addDays()`, `->addYear()`... (Carbon). Khi serialize model, Laravel xuất datetime dạng **ISO-8601 UTC** (ví dụ `2026-07-18T09:30:00.000000Z`) trừ khi model có `$casts`/`$dateFormat` tùy biến.
- Một số chỗ cast `(string) $subscription->end_date` cho audit → định dạng phụ thuộc cast của model (có thể `Y-m-d H:i:s`). **Không nhất quán** giữa các field.

### A.9. Tiền tệ / số thập phân
- Amount lưu & trả dạng **số thập phân**, cast `(float)` trong controller (`(float) $plan->monthly_base_price`, `mrr`, `total_amount`...).
- Currency **hard-code `'VND'`** khi tạo subscription/invoice; không có bảng tỷ giá đa tiền tệ.
- Thuế VAT **hard-code 10%** (`round($subtotal * 0.1)`), `tax_rate => 10`.
- **Không dùng minor units (integer)**; là float — có rủi ro sai số dấu phẩy động cho tính toán tiền.

### A.10. i18n / localization
- `lang/` chỉ có `lang/vendor/filament-shield/vi/filament-shield.php` (bản dịch package). **Không có** `lang/vi/` / `lang/en/` do team tự viết cho message API.
- `config('app.locale') = 'en'`, `fallback_locale = 'en'` ⇒ message validation mặc định **tiếng Anh**, trong khi message nghiệp vụ ad-hoc lại **tiếng Việt** (lẫn lộn, một số không dấu). Không có cơ chế `Accept-Language`.

### A.11. Audit
Mọi hành động thay đổi trạng thái gọi trait audit (`WritesBillingAudit`/`WritesIntegrationAudit`/`WritesSupportAudit`) ghi log kèm before/after. Đây là hành vi tốt cần **giữ** cho Mobile API.

---

## PHẦN B — Chuẩn KHUYẾN NGHỊ cho Mobile API (đề xuất)

> Áp dụng cho tầng API mới (`/api/mobile/*` hoặc `/api/v1/*`) phục vụ Flutter cư dân + app BQL. Bám sát những gì codebase đã làm; chỉ chuẩn hóa thêm.

### B.1. Xác thực
- Dùng **Laravel Sanctum** (personal access token) cho mobile: đăng nhập trả `token`, client gửi `Authorization: Bearer <token>`.
- Phân tách rõ **401** (chưa/không xác thực, token sai/hết hạn) với **403** (đã xác thực nhưng thiếu quyền) — khác với hiện tại gộp thành 403.
- Áp scope theo tenant/project của người dùng (không để client tự truyền `tenant_id` như API platform).

### B.2. Envelope thành công (thống nhất)
Bọc mọi phản hồi để client parse một kiểu duy nhất:
```json
{
  "success": true,
  "data": { /* object hoặc array */ },
  "meta": null
}
```
List + phân trang:
```json
{
  "success": true,
  "data": [ /* items */ ],
  "meta": {
    "pagination": {
      "current_page": 1,
      "per_page": 20,
      "total": 137,
      "last_page": 7,
      "has_more": true
    }
  }
}
```
> Triển khai bằng **API Resources** (`JsonResource` + `ResourceCollection`) — hiện repo **chưa có** `app/Http/Resources`; nên tạo mới để tách hình dạng phản hồi khỏi model (tránh rò field nhạy cảm như password/secret khi trả model thô).

### B.3. Envelope lỗi (thống nhất) + mã lỗi máy đọc
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_FAILED",
    "message": "Dữ liệu không hợp lệ.",
    "fields": {
      "phone": ["Số điện thoại không hợp lệ."]
    }
  }
}
```
Bảng mã đề xuất (HTTP ↔ code):

| HTTP | error.code | Khi nào |
|---|---|---|
| 400 | BAD_REQUEST | request sai cú pháp/tham số |
| 401 | UNAUTHENTICATED | thiếu/hỏng token |
| 403 | FORBIDDEN | không đủ quyền / sai scope tenant |
| 404 | NOT_FOUND | model binding không thấy |
| 409 | CONFLICT | vi phạm trạng thái (vd kỳ usage chưa khóa, căn hộ đã gán) |
| 422 | VALIDATION_FAILED | lỗi validate (field-level trong `error.fields`) |
| 429 | RATE_LIMITED | quá giới hạn |
| 500 | SERVER_ERROR | lỗi hệ thống |

- Business error (hiện đang ad-hoc `{message:...}` + 422) → chuẩn hóa về **409 CONFLICT** với `error.code` riêng (vd `USAGE_PERIOD_NOT_LOCKED`).
- Chuẩn hóa qua `App\Exceptions\Handler` (render JSON envelope cho toàn `/api/mobile/*`), tái dùng `shouldRenderJsonWhen` đã có.

### B.4. Phân trang
- Giữ query `?per_page=` (như hiện tại) nhưng **giới hạn max** (vd ≤100) và trả `meta.pagination` gọn (B.2) thay vì paginator thô Laravel → client mobile parse ổn định, không phụ thuộc `links[]`/URL tuyệt đối.
- Hỗ trợ cursor pagination cho danh sách lớn (feed thông báo, lịch sử) nếu cần cuộn vô hạn.

### B.5. Ngày giờ & timezone
- **Lưu UTC** (giữ nguyên `config('app.timezone')='UTC'`).
- **Xuất ISO-8601 có offset Z**: `2026-07-18T09:30:00Z` cho MỌI field datetime (chuẩn hóa qua Resource, tránh tình trạng lẫn `Y-m-d H:i:s` như A.8).
- Client tự đổi sang giờ VN (`Asia/Ho_Chi_Minh`, UTC+7) để hiển thị. Có thể cho phép `?tz=` hoặc header, nhưng payload luôn UTC.

### B.6. Tiền tệ / số
- Khuyến nghị trả tiền dạng **chuỗi decimal cố định** (vd `"1500000.00"`) HOẶC **integer minor units** kèm field `currency` + `currency_scale`, để tránh sai số float hiện tại (A.9). Với VND (không có phần lẻ), minor unit = đồng, `scale=0`.
- Luôn kèm `currency` tường minh (không hard-code phía client). VAT/tax nên trả cả `tax_rate` và số tiền đã tính (như invoice hiện có), không để client tự suy.

### B.7. i18n
- Bổ sung `lang/vi/` (+ `lang/en/`) cho message nghiệp vụ; đọc `Accept-Language` (mặc định `vi` cho mobile).
- Message người dùng đọc → tiếng Việt có dấu, nhất quán (sửa các chuỗi không dấu như `"Ky usage chua khoa"`). `error.code` giữ tiếng Anh (máy đọc), `error.message` theo locale.

### B.8. Versioning & bảo mật
- Prefix version rõ (`/api/v1/...`) để tiến hóa không phá app cũ.
- **Không trả model thô**: dùng Resource để whitelist field (đặc biệt không lộ `password`, `encrypted_payload`, token). Secret (nếu có) giữ nguyên nguyên tắc "trả 1 lần" như Integration hiện tại.
- Rate limit (`throttle`) cho endpoint nhạy cảm (login, OTP, reset password).
- Giữ **audit log** cho mọi hành động thay đổi trạng thái (tái dùng các trait `Writes*Audit` đã có).

### B.9. Tương thích với nghiệp vụ hiện có
Khi bọc nghiệp vụ từ Filament (§6 của file inventory) thành API, tái dùng service/logic sẵn có thay vì viết lại:
- Reset mật khẩu cư dân: tái dùng `Password::broker()->createToken()` (như `ResetsResidentPassword`) nhưng trả JSON; cân nhắc endpoint OTP riêng (hiện OTP lưu `Cache::put('resident_pwd_otp_'.$id, ..., 10 phút)`).
- Đổi trạng thái căn hộ / duyệt xe / cấp thẻ / khóa-mở tài khoản: mỗi cái thành endpoint POST idempotent, trả model qua Resource + ghi audit.
