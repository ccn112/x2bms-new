# KHOẢNG TRỐNG API CHO MOBILE — X2-BMS

> Ngày audit: 2026-07-18 · READ-ONLY. Kèm đường dẫn file cụ thể.
> **Sự thật nền tảng:** `routes/api.php` chỉ có 3 group `platform.admin` (billing/integration/support SaaS). **Không có endpoint nào cho cư dân hoặc BQL.** Không route nào dùng `auth:sanctum`; chỉ `app/Models/User.php` có `HasApiTokens`. Mọi endpoint dưới đây là **cần xây mới**, không phải "sửa" cái đang có.

---

## A. API còn thiếu cho FLUTTER APP CƯ DÂN

Toàn bộ đều **chưa tồn tại**. Đề xuất prefix `/api/resident/*`, guard `auth:sanctum` (ability `resident`).

### A1. Auth & tài khoản
| Endpoint đề xuất | Method | Ghi chú / model liên quan |
|---|---|---|
| `/api/auth/login` | POST | Cấp Sanctum token. Hiện chỉ `User` có `HasApiTokens`; `Resident` là membership tách rời (`Resident.user_id` → `User`). Cần quyết định chủ thể token là `User` (account toàn cục) hay `Resident`. |
| `/api/auth/otp/request` + `/api/auth/otp/verify` | POST | **Chưa có hạ tầng OTP** (không thấy model/bảng OTP, không cột `phone_verified_at`). Cần xây mới. |
| `/api/auth/logout` | POST | Thu hồi token. |
| `/api/auth/password/forgot` + `/reset` | POST | Đã có luồng **web guest** `ResidentPasswordResetController` (`routes/web.php` dòng 13-14) nhưng là form HTML, token do BQL sinh — chưa có API JSON. |
| `/api/me` (profile) | GET | Model `Resident`, `GlobalUserAccount`, `ResidentEmergencyContact`. |
| `/api/me/household` | GET | `ResidentApartmentRelation` (quan hệ hộ), `HouseholdRelationships` page có logic tham khảo. |
| `/api/me/apartments` | GET | Căn hộ của cư dân qua `resident_apartment_relations`. |

### A2. Hoá đơn phí & công nợ
| Endpoint | Method | Model / Page tham khảo |
|---|---|---|
| `/api/me/statements` | GET | `Statement`, `StatementLine` · Page `StatementList` |
| `/api/me/statements/{id}` | GET | `StatementDetail` |
| `/api/me/debts` | GET | `Debt` · Page `DebtLedger`, `DebtAgingList` |

### A3. Thanh toán
| Endpoint | Method | Ghi chú |
|---|---|---|
| `/api/me/payments` | GET | Lịch sử `Payment`, `Receipt`, `PaymentAllocation`. |
| `/api/me/payments` | POST | **Tạo thanh toán** — chưa có. `PaymentGatewayConfig`, `QrPaymentToken` có model nhưng **không cổng online cho cư dân**. **Cần idempotency key** (xem §D). |
| `/api/me/payments/{id}/status` | GET | Tra cứu trạng thái giao dịch. |
| Webhook cổng thanh toán | POST | Chưa có endpoint nhận callback cổng thanh toán cho phí cư dân. |

### A4. Phản ánh / yêu cầu dịch vụ
| Endpoint | Method | Model / Page |
|---|---|---|
| `/api/me/feedback` | GET/POST | `FeedbackRequest`, `FeedbackAttachment`, `FeedbackComment` · Page `FeedbackQueue`. Enum `FeedbackStatus`. |
| `/api/me/feedback/{id}` | GET | Theo dõi tiến độ + `FeedbackStatusHistory`. |
| `/api/me/feedback/{id}/comments` | POST | `FeedbackComment`. |

### A5. Đặt tiện ích (Amenity booking)
| Endpoint | Method | Ghi chú |
|---|---|---|
| `/api/amenities` + `/{id}/slots` | GET | `Amenity`, `AmenitySlot` — **model có, KHÔNG có logic**. |
| `/api/me/bookings` | GET/POST | `AmenityBooking`, `BookingQrPass` — **chưa có Filament Page/Resource nào**; nghiệp vụ đặt chỗ (giữ slot, chống trùng) **phải xây từ đầu**, không có logic để tái dùng. |

### A6. Khách & QR ra vào
| Endpoint | Method | Ghi chú |
|---|---|---|
| `/api/me/visitors` | GET/POST | `VisitorRegistration`, `VisitorPass` — **model có, không Page/Resource**. Sinh QR/mã khách cần xây mới. |
| `/api/me/packages` | GET | `PackageDelivery` — model có, không logic. |

### A7. Thông báo
| Endpoint | Method | Model |
|---|---|---|
| `/api/me/notifications` | GET | `Notification`, `NotificationAudience`, `NotificationChannel` · Page `NotificationCenter`. |
| `/api/me/notifications/{id}/read` | POST | `NotificationRead` (bảng read-tracking đã có sẵn — thuận lợi). |
| `/api/me/notifications/read-all` | POST | Bulk. |
| `/api/me/device-tokens` | POST | **Chưa có** bảng push token (FCM/APNs). Cần migration mới. `NotificationDeliveryLog` có nhưng không có kênh push mobile. |

### A8. Tài liệu căn hộ
| Endpoint | Method | Model |
|---|---|---|
| `/api/me/documents` | GET | `Document`, `DocumentLibrary`, `DocumentVersion`. |
| `/api/me/documents/{id}/download` | GET | Cần signed URL; hiện tải qua Filament. |

---

## B. API còn thiếu cho FLUTTER APP BQL

Toàn bộ **chưa tồn tại**. Đề xuất prefix `/api/bql/*`, guard `auth:sanctum` + policy theo domain (hiện chỉ 9 policy — thiếu cho hầu hết domain BQL).

| Nhóm | Endpoint đề xuất | Page/Model đang chứa logic (cần bóc ra service) |
|---|---|---|
| Quản lý cư dân | `GET/POST/PUT /api/bql/residents(+/{id})` | `ResidentDirectory/Detail/Create` · `Resident`, `ResidentApartmentRelation` |
| Duyệt binding/tài khoản | `GET /api/bql/approvals`, `POST /{id}/approve|reject` | `ResidentApprovalQueue`, `ResidentBindingQueue`, `AccountApprovalDetail` · `ResidentBindingRequest`, `ResidentApprovalRequest`, `ApprovalStep`. Enum `ResidentApprovalStatus`. |
| Vận hành căn hộ | `GET/PUT /api/bql/apartments(+/{id})` | `ApartmentDirectory/Profile/Tree`, `MoveInOutHistory` · `Apartment`, `ApartmentStatusHistory` |
| Gửi thông báo | `POST /api/bql/notifications` | `NotificationCenter` · `Notification`, `NotificationAudience`, `NotificationDeliveryLog` |
| Phản ánh / work-order | `GET /api/bql/feedback`, `POST /{id}/assign|status`, `GET /api/bql/work-orders`, kanban move | `FeedbackQueue`, `WorkOrderKanban`, `MyWork` · `FeedbackRequest`, `WorkOrder(+Assignment/Checklist/Signature)`. Enum `WorkOrderStatus`. |
| Xe / thẻ | `GET/POST /api/bql/vehicles`, `/access-cards`, duyệt yêu cầu | `VehiclesAndCards`, `VehicleRequests`, `AccessCards`, `AccessControlDashboard` · `Vehicle`, `AccessCard`, `AccessLog`. Enum `VehicleType`. |
| Công nợ / phí | `GET /api/bql/debts`, `/statements`, duyệt kỳ phí | `DebtLedger`, `DebtAgingList`, `StatementApprovalQueue`, `FeeCycleList` · `Debt`, `Statement`, `StatementApproval` |
| Báo cáo | `GET /api/bql/reports/*` | `OperationalDashboard`, `AuditLogViewer` · `MetricSnapshot`, `ReportExportJob`, `ReportSchedule`, `ExportJob` |

**Rào cản chung cho cả BQL app:** logic nằm trong Livewire component (state UI + query trộn lẫn), chưa tách service; phân quyền dựa vào lọc scope trong query (`buildingIds()`/`CurrentContext`) chứ không phải Gate/Policy → khó tái hiện an toàn qua API nếu không refactor.

---

## C. Endpoint cần sửa / dùng sai HTTP method

1. **GET có side-effect trong `routes/web.php` (vi phạm nguyên tắc GET an toàn — quan trọng cho mobile/prefetch/crawler):**
   - `GET /context/project/{project}` (dòng 18-32): **ghi state phiên** (`setProject`) **+ tạo bản ghi `AuditLog::create`**. Là GET nhưng mutate. Nên chuyển POST.
   - `GET /context/workspace/{key}` (dòng 35-55): `setWorkspace` + `AuditLog::create` rồi redirect. GET-with-side-effect.
   - `GET /context/hq-tenant/{tenant}` (dòng 66-76): ghi `session(['hq_tenant_id' => …])`. GET-with-side-effect.
   - (`POST /context/hq-projects` dòng 58-63 đã đúng method.)
   - → Khi làm mobile/SPA cần bản POST/PUT tương đương cho việc đổi ngữ cảnh; tránh để client GET vô tình đổi ngữ cảnh + đẻ audit log.
2. **`GET /reset-password/{token}`** (`routes/web.php` dòng 13): chỉ render form (không mutate) — **chấp nhận được**. Bản mutate là `POST /reset-password` (dòng 14) — đúng. Nhưng token đặt trong **URL path** của link email; với API nên tránh nhét token nhạy cảm vào URL (dùng body).
3. **API billing platform** (`routes/api.php`): dùng POST cho nhiều hành động lifecycle (`/subscriptions/{}/upgrade|pause|renew`, `/invoices/{}/approve|send|void`, `/wallets/{}/top-up|deduct`…). Đây là **RPC-style hợp lệ** cho hành động, **không phải lỗi** — ghi nhận để không nhầm là bug. Chỉ lưu ý khi thiết kế API cư dân/BQL nên nhất quán phong cách này.

---

## D. Quan ngại N+1 / hiệu năng

- **Tin tốt:** các Page domain cư dân đã build (BQL-01) **có eager-load hợp lý**, rủi ro N+1 thấp:
  - `ResidentDirectory.php`: `filteredQuery()` (dòng 169-174) eager-load `apartmentRelations.apartment.floor`, `building`; `primaryRelation()` (`Resident.php` dòng 74-76) chỉ đọc **collection đã nạp** (`$this->apartmentRelations->firstWhere(...)`), không query lại. Export CSV (dòng 254) cũng dùng `filteredQuery()` → an toàn.
  - `MoveInOutHistory.php` (dòng 50, 65), `HouseholdRelationships.php` (dòng 55), `ResidentTimeline.php` (dòng 69, 81), `ApartmentDirectory.php`: đều `->with([...])` phủ quan hệ dùng trong vòng lặp render.
- **Rủi ro tiềm ẩn khi lên API:** logic query gắn chặt vào component Livewire; nếu bóc ra API mà quên mang theo `->with()` sẽ tái sinh N+1. Cần đưa eager-load vào tầng service dùng chung.
- **`whereHas` không kèm eager-load để hiển thị:** phần lớn `whereHas` ở các Page chỉ dùng để **lọc** (không đọc quan hệ đó khi render) nên không N+1 — nhưng đây là mẫu dễ sai khi nhân bản sang endpoint mới.
- **Không có tầng cache/summary API:** dashboard (`OperationalDashboard`) tính trực tiếp; mobile cần endpoint tổng hợp nhẹ (đếm/aggregate) thay vì kéo full list.

---

## E. Quan ngại offline / đồng bộ cho mobile

- **Không có endpoint sync theo `updated_at`.** Không thấy pattern `?since=`/delta-sync ở đâu.
- **Không có ETag / If-Modified-Since / Last-Modified** trên bất kỳ response nào (API hiện trả JSON từ controller platform, không set header cache/validator).
- **Không có cursor pagination.** `grep cursorPaginate` toàn repo = **0 kết quả**. Danh sách trong Filament dùng offset paginate của Livewire (không dùng lại được cho mobile); API platform trả về theo query thường. Với dữ liệu lớn (thông báo, giao dịch) cần cursor để mobile cuộn ổn định.
- **Không có soft-delete/tombstone nhất quán để client biết bản ghi đã xoá** khi đồng bộ (cần kiểm tra `deleted_at` từng bảng nếu triển khai delta-sync).
- → Trước khi làm offline-first: thêm `updated_at`-based delta endpoint + cursor pagination + trường tombstone.

---

## F. Quan ngại idempotency

- **Không có idempotency key trên bất kỳ endpoint write/payment nào.** `grep idempoten*` chỉ khớp trong **domain Integration/Webhook** (`IntegrationEventController`, `IntegrationConnection`, migration batch08) — tức idempotency của **webhook events**, KHÔNG phải của thanh toán.
- Các endpoint tài chính hiện có (`/wallets/{}/top-up|deduct`, `/invoices/{}/payments`, `/invoices/generate`) **không nhận `Idempotency-Key`** → mobile retry (mất mạng, người dùng bấm lại) có nguy cơ **tạo giao dịch trùng**.
- Khi thiết kế `POST /api/me/payments` và mọi write tài chính cho cư dân: **bắt buộc** header `Idempotency-Key` + bảng lưu key→response để trả lại kết quả cũ khi trùng.

---

## Tóm tắt hành động nền tảng (thứ tự đề xuất)
1. Auth token cho cư dân (Sanctum ability, quyết định chủ thể `User` vs `Resident`) + OTP (chưa có hạ tầng).
2. Tách logic Filament Page → service dùng chung; dựng `/api/resident/*` và `/api/bql/*` với `auth:sanctum` + policy đầy đủ (hiện thiếu policy cho hầu hết domain).
3. Xây mới 2 module trống: Amenity booking, Guest/QR (visitor + package).
4. Chuẩn mobile: cursor pagination, delta-sync theo `updated_at`, ETag; idempotency key cho mọi write tài chính; bảng device push token + kênh push trong Notification.
5. Chuyển các `GET /context/*` có side-effect sang POST; không nhét token vào URL cho API.
