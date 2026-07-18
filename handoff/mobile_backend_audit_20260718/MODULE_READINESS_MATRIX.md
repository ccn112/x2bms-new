# MA TRẬN MỨC ĐỘ SẴN SÀNG THEO MODULE — X2-BMS (audit mobile backend)

> Ngày audit: 2026-07-18 · Chế độ: **READ-ONLY** (không sửa source/migration/route/config).
> Stack đã xác minh: Laravel 13, Filament **v5**, Sanctum (chỉ `User` dùng `HasApiTokens`), spatie/permission.
> Quy mô: **284 model** (`app/Models`), **67 migration** (`database/migrations`), **36 Filament Page** (`app/Filament/Pages`), ~130 Filament Resource (`app/Filament/Resources`), **9 Policy**, **2 Seeder**, **6 file test**.

## Điểm mấu chốt cần đọc trước ma trận

1. **`routes/api.php` CHỈ dành cho platform-admin** (SaaS billing / integration / support). Toàn bộ 3 group đều bọc middleware `platform.admin` (`app/Http/Middleware/EnsurePlatformAdmin.php`) — chặn ai không phải `isPlatformAdmin()`. **Không có một endpoint API nào cho cư dân hay BQL.**
2. **Không có xác thực API kiểu token cho mobile.** Chỉ `app/Models/User.php` khai báo `HasApiTokens`; không route nào dùng guard `auth:sanctum`. API hiện tại xác thực qua **phiên Filament** (`$request->user()`), tức là session cookie — không dùng được cho app Flutter.
3. **Toàn bộ nghiệp vụ cư dân/BQL nằm trong Livewire (Filament Page/Resource), KHÔNG có tầng service tái sử dụng.** Logic query + hành động (duyệt, đổi trạng thái, xuất CSV, reset mật khẩu…) viết thẳng trong component. Muốn có API phải bóc logic ra service trước.
4. **Cột "service"** dưới đây = có nơi chứa nghiệp vụ (Filament Page hoặc service trong `app/Support`). **Cột "API"** = có endpoint HTTP dùng được cho client ngoài. Gần như mọi module **có model + Filament nhưng ❌ API.**
5. **Enum:** chỉ 4 PHP enum (`FeedbackStatus`, `ResidentApprovalStatus`, `VehicleType`, `WorkOrderStatus`); còn lại status là cột string tự do → mobile phải tự map/whitelist giá trị.
6. **Seed:** `database/seeders/DemoDataSeeder.php` seed gần như **toàn bộ** model (đã thấy `Amenity`, `AmenityBooking`, `BookingQrPass`, `Loyalty*`, `Marketplace*`, `Ai*`… đều có `::create`). Nên cột seed chủ yếu ✅ (dữ liệu demo, không phải seed cấu hình chuẩn).
7. **Policy:** chỉ 9 policy cho lõi tổ chức/cư dân (`Apartment/Building/Department/Floor/Project/Resident/Role/Tenant/User`). Mọi domain khác (notification, invoice, payment, feedback, vehicle, document, community, marketplace, loyalty, smart-home, AI) **không có policy** — phân quyền hiện dựa vào spatie/permission + lọc scope trong query của Page, không phải Gate/Policy.

## Ma trận

Chú thích: ✅ đủ · ⚠️ một phần · ❌ thiếu · n/a không áp dụng.
Cột **service** = Filament Page/Resource hoặc service `app/Support`. Cột **mobile-ready** = mức sẵn sàng cho *bất kỳ* app mobile (chủ yếu bị chặn bởi thiếu API).

| Module | migration | model | service (Filament/logic) | API | policy | seed | test | mobile-ready | Ghi chú (model/page cụ thể) |
|---|---|---|---|---|---|---|---|---|---|
| **Resident / Apartment** | ✅ | ✅ | ✅ | ❌ | ✅ | ✅ | ❌ | ❌ | Models: `Resident`, `Apartment`, `ResidentApartmentRelation`, `ResidentEmergencyContact`, `ApartmentStatusHistory`, `Floor/Block/Area/Building`. Pages: `ResidentDirectory/Detail/Create/Timeline/DataQuality`, `ApartmentDirectory/Profile/Tree`, `HouseholdRelationships`, `MoveInOutHistory`. Policy: `ResidentPolicy`, `ApartmentPolicy`. Domain trưởng thành nhất nhưng chỉ chạy trong /admin. |
| **Binding / Duyệt tài khoản** | ✅ | ✅ | ✅ | ❌ | ❌ | ✅ | ❌ | ❌ | Models: `ResidentBindingRequest`, `ResidentApprovalRequest`, `ResidentUnitBinding`, `ApprovalRequest`, `ApprovalStep`. Pages: `ResidentApprovalQueue`, `ResidentBindingQueue`, `AccountApprovalDetail`. Enum `ResidentApprovalStatus`. Reset mật khẩu qua web (xem MOBILE_API_GAPS §method). Không policy riêng. |
| **Notification** | ✅ | ✅ | ✅ | ❌ | ❌ | ✅ | ❌ | ❌ | Models: `Notification`, `NotificationAudience`, `NotificationChannel`, `NotificationDeliveryLog`, `NotificationRead`. Page: `NotificationCenter`. Có sẵn cấu trúc read-tracking + delivery-log nhưng **không endpoint list/mark-read**; không push token/FCM. |
| **Invoice / Debt (phí cư dân)** | ✅ | ✅ | ✅ | ❌ | ❌ | ✅ | ❌ | ❌ | Models: `Statement`, `StatementLine`, `StatementApproval`, `StatementPublishLog`, `Debt`, `DebtReminderCampaign/Log`, `FeeType/FeeRate/FeeFormula`. Pages: `StatementList/Detail/ApprovalQueue`, `DebtLedger`, `DebtAgingList`, `FeeCatalog`, `FeeCycleList`. (Lưu ý: `BillingInvoice*` là **billing SaaS**, khác hoá đơn phí cư dân.) |
| **Payment** | ✅ | ✅ | ⚠️ | ⚠️ (chỉ SaaS) | ❌ | ✅ | ⚠️ (chỉ SaaS) | ❌ | Models cư dân: `Payment`, `PaymentRequest`, `PaymentAllocation`, `Receipt`, `PaymentGatewayConfig`, `QrPaymentToken`, `CashFund/CashVoucher`, `Wallet/WalletTransaction`. Resources: `Payments`, `PaymentRequests`, `QrPaymentTokens`. API `platform/billing/invoices/{}/payments` chỉ cho **billing SaaS platform-admin**, không phải thanh toán phí của cư dân. Không cổng thanh toán online cho cư dân, **không idempotency key**. |
| **Feedback / Work-order** | ✅ | ✅ | ✅ | ❌ | ❌ | ✅ | ❌ | ❌ | Models: `FeedbackRequest`, `FeedbackAssignment/Comment/Attachment/Category/StatusHistory`, `WorkOrder(+Assignment/Attachment/Checklist/Signature)`. Pages: `FeedbackQueue`, `WorkOrderKanban`, `MyWork`. Enum `FeedbackStatus`, `WorkOrderStatus`. Không API tạo/tra cứu phản ánh. |
| **Amenity booking (tiện ích)** | ✅ | ✅ | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ | Models: `Amenity`, `AmenityBooking`, `AmenitySlot`, `BookingQrPass`. **Không tìm thấy Filament Page/Resource nào** cho đặt tiện ích (chỉ có model + seed demo). Nghiệp vụ đặt chỗ chưa được xây ở bất kỳ tầng nào. |
| **Guest / QR (khách + gói hàng)** | ✅ | ✅ | ⚠️ | ❌ | ❌ | ✅ | ❌ | ❌ | Models: `VisitorPass`, `VisitorRegistration`, `PackageDelivery`, `BookingQrPass`, `QrPaymentToken`. **Không tìm thấy Page/Resource cho VisitorPass/VisitorRegistration/PackageDelivery**. Chỉ `QrPaymentTokens` (QR thanh toán) có Resource. Đăng ký khách/QR mở cửa cho mobile chưa có logic. |
| **Vehicle / Access card** | ✅ | ✅ | ✅ | ❌ | ❌ | ✅ | ❌ | ❌ | Models: `Vehicle`, `AccessCard`, `AccessDevice`, `AccessLog`, `IntercomEvent`. Pages: `VehiclesAndCards`, `VehicleRequests`, `AccessCards`, `AccessControlDashboard`, `ResidentAccessProfile`. Enum `VehicleType`. Resources `AccessDevices`, `AccessLogs`. Không API đăng ký xe/thẻ cho cư dân. |
| **Apartment documents** | ✅ | ✅ | ✅ | ❌ | ❌ | ✅ | ❌ | ❌ | Models: `Document`, `DocumentLibrary`, `DocumentVersion`, `DocumentTemplate(+Category/Clone/Share)`, `TemplateAssignment`. Resources: `DocumentTemplates`, `DocumentTemplateCategories`. Service `app/Support/Knowledge/DocumentTextExtractor.php`. Không endpoint tải tài liệu theo căn hộ cho cư dân. |
| **Handover / Warranty** | ✅ | ✅ | ✅ | ❌ | ❌ | ✅ | ❌ | ❌ | Models: `HandoverBatch`, `HandoverUnit`, `HandoverChecklist`, `HandoverPunchItem`, `WarrantyRequest`. Resources: `HandoverBatches`, `WarrantyRequests`. Nghiệp vụ nghiệm thu/bảo hành có Filament nhưng không API. |
| **Community** | ✅ | ✅ | ✅ | ❌ | ❌ | ✅ | ❌ | ❌ | Models: `CommunityGroup`, `CommunityPost`, `Poll(+Option/Vote)`, `Event(+Registration)`, `EmergencyAlert`, `SosAlert`. Resources: `CommunityPosts`, `Polls`, `Events`, `EmergencyAlerts`, `SosAlerts`. Không API feed/bình chọn/đăng ký sự kiện cho cư dân. |
| **Marketplace** | ✅ | ✅ | ⚠️ | ❌ | ❌ | ✅ | ❌ | ❌ | Models: `MarketplaceProduct`, `MarketplaceOrder`, `OrderItem`, `RealEstateListing`, `ListingInquiry`, `SharedPartner(+Category/Product/Certification)`. Resources: `MarketplaceProducts`, `RealEstateListings`, `SharedPartners`. Chỉ có quản trị sản phẩm; luồng mua/đặt hàng của cư dân chưa có. |
| **Loyalty** | ✅ | ✅ | ⚠️ | ❌ | ❌ | ✅ | ❌ | ❌ | Models: `LoyaltyAccount`, `LoyaltyTransaction`, `Voucher`. Resources: `LoyaltyAccounts`, `Vouchers`. Quản trị điểm/voucher có; API tích/tiêu điểm cho cư dân chưa có. |
| **Smart home** | ✅ | ✅ | ⚠️ | ❌ | ❌ | ✅ | ❌ | ❌ | Models: `SmartHomeAccount`, `SmartDevice`, `SmartScene`, `IotDevice`, `SensorEvent`, `EnergyReading`, `Meter/MeterReading`. Resources: `SmartDevices`, `SmartHomeAccounts`, `IotDevices`, `Meters`. Chỉ đăng ký/liệt kê thiết bị; không API điều khiển/streaming cho mobile. |
| **AI / RAG** | ✅ | ✅ | ✅ | ❌ (nội bộ) | ❌ | ✅ | ❌ | ❌ | Models: `Ai*` (Request/Approval/Insight/Suggestion/Policy/GuardrailPolicy/PromptTemplate/RetrievalLog/UsageLog/Workflow(+Run)/TestRun…), `Knowledge*` (Document/Chunk/Article/Category/Scope). Resources: `AiApprovals`, `AiRequests`, `AiRetrievalLogs`, `AiGuardrailPolicies`, `KnowledgeDocuments`. Services: `app/Support/X2AI/*` (`X2aiClient`, `X2aiDataConnector`, `X2aiKnowledgeConnector`, `X2aiPolicyGate`). Rule-based, không LLM. Không endpoint chat/RAG public cho mobile. |

## Tổng kết mức sẵn sàng theo 3 mục tiêu mobile

### 1. Flutter App CƯ DÂN — **CHƯA sẵn sàng (0% bề mặt API)**
- **Chặn cứng:** không có API cư dân nào; không có auth token cho cư dân (chỉ `User` có `HasApiTokens`, `Resident` là membership tách khỏi `User`); toàn bộ luồng ở Livewire.
- Data model **đã có** cho hầu hết nhu cầu (hồ sơ hộ, hoá đơn/công nợ, phản ánh, thông báo, xe/thẻ, tài liệu). Cần bọc thành REST/JSON.
- **Hai module còn thiếu cả logic:** Amenity booking (chỉ model) và Guest/QR khách+gói hàng (chỉ model). Marketplace/Loyalty/Smart-home mới có tầng quản trị, chưa có luồng cư dân.

### 2. Flutter App BQL — **CHƯA sẵn sàng (0% bề mặt API)**
- Nghiệp vụ BQL đầy đủ nhất trên web (residents, duyệt binding, phản ánh/work-order, xe/thẻ, thông báo) nhưng **100% trong Filament**, không tái sử dụng qua API.
- Không có tầng service; muốn ra app phải refactor logic Page → service + controller, rồi thêm `auth:sanctum` + policy cho từng domain (hiện chỉ 9 policy).

### 3. Web cư dân (resident web) — **CHƯA sẵn sàng**
- Chưa có panel/portal cư dân riêng; `/` → `/admin` (dành cho BQL). Chỉ có 1 luồng guest: đặt lại mật khẩu (`routes/web.php` + `ResidentPasswordResetController`).
- Nếu làm resident web dạng SPA sẽ vấp cùng rào cản thiếu API như app mobile; nếu làm bằng Filament panel mới thì tái dùng được Livewire nhưng vẫn cần policy + tách scope cư dân.

### Ưu tiên nền tảng trước khi build mobile
1. **Auth cư dân**: cấp token Sanctum cho `Resident`/`GlobalUserAccount` (không chỉ `User`) + login/OTP.
2. **Tầng service**: bóc logic từ Filament Page ra `app/Services|Support` để cả web-admin và API dùng chung.
3. **Policy phủ hết domain** (hiện thiếu cho notification/invoice/payment/feedback/vehicle/document/community/marketplace/loyalty/smart-home/AI).
4. **Xây 2 module còn trống**: Amenity booking, Guest/QR (visitor + package).
5. **Test**: hiện chỉ 6 file test, đều cho API platform (Batch07/08/10). Không test nào cho domain cư dân/BQL.
