# ADR-001 (kiến trúc) — Kỷ luật tenant scope: `withGlobalScope` vs `withoutGlobalScopes()`

- Trạng thái: **ACCEPTED** · 2026-08-04
- Liên quan: gate **G9 (anti-bypass)** `docs/delivery/03_VERTICAL_SLICE_GATES.md`; **TECH_DEBT T1**
  ("không dùng `tenant_id` đơn thuần làm bằng chứng cô lập"); rule
  `.claude/rules/x2bms-laravel-domain.md` ("Any query returning tenant/business data must show
  its scope mechanism in code and tests").

## Bối cảnh
Đa tenant single-DB bằng row-level qua trait `BelongsToTenant`: global scope tự lọc theo
`tenant_id` của người đăng nhập, và tự điền `tenant_id` khi tạo. Rủi ro:
1. `withoutGlobalScopes()` **tắt** lớp lọc này — dùng sai chỗ là rò dữ liệu tenant khác.
2. Trong **console** (seeder/migration/command/queue) global scope là **no-op** (không có auth),
   nên mọi truy vấn console mặc định KHÔNG bị lọc — phải tự chủ ý về scope.
3. Cô lập chỉ dựa vào global scope là "mềm": một chỗ quên là lọt (đúng họ lỗi T1).

## Quyết định
1. **Web (có auth):** đọc/ghi dữ liệu tenant DỰA vào global scope `BelongsToTenant` của người
   đăng nhập. Không tự `where('tenant_id', ...)` rải rác thay cho scope.
2. **Platform admin (`isPlatformAdmin`)** được **bypass** tenant scope (thấy cross-tenant) — CHỦ Ý,
   cổng bằng cờ `users.is_platform_admin`. Đây là lý do `BelongsToTenant::currentTenantId()` trả
   `null` cho platform admin. Không bao giờ suy ra quyền này từ dữ liệu quan sát được.
3. **`withoutGlobalScopes()` CHỈ dùng khi:**
   - chạy trong **console** (seeder/migration/command) — nơi global scope vốn đã no-op; hoặc
   - code **tự áp lại một scope tường minh** ngay sau đó (vd `scopeVisibleTo`, resolver có
     `where('building_id', ...)`).
   - **CẤM** trong đường phục vụ dữ liệu tenant cho web/API mà không re-scope tường minh.
4. **Uỷ quyền nhắm chéo-aggregate** (vd chọn tòa/căn khi soạn thông báo) PHẢI **validate phía
   server** theo phạm vi người dùng (`accessibleProjectIds`/tenant), KHÔNG chỉ ẩn lựa chọn ở form
   (G9). Có test gọi thẳng đường vòng → phải bị từ chối.
5. **Bằng chứng bằng TEST**, không bằng niềm tin: mỗi bề mặt đọc/ghi tenant có ≥1 test
   `MUST_NOT_LEAK` (tenant A không thấy/không sửa được dữ liệu tenant B; platform admin thì được).

## Áp dụng đã có (2026-08-04, module thông báo)
- `Notification::scopeVisibleTo` + `canManageBy`: cô lập đọc/quản trị theo cấp.
- `NotificationCenter::audienceTargetOptions` bó danh sách theo `accessibleProjectIds`/tenant;
  `createNotification` + hành động Phát hành/Lưu trữ **validate lại phía server**.
- Test: `tests/Feature/NotificationAudienceScopeTest.php` (đọc, quản trị, và Livewire compose
  bypass đều bị chặn).

## Hệ quả & còn nợ (chưa hard-lock tầng DB)
- Cô lập hiện vẫn ở tầng **query/scope + test**, CHƯA có ràng buộc tầng DB (CHECK/FK/RLS) chặn
  tuyệt đối mọi code path. Đây là bước "hard-lock" mạnh hơn — đưa vào TECH_DEBT để làm dần cho
  các bảng nhạy cảm.
- Bảng thiếu `tenant_id` (vd `comments` — T1) cô lập gián tiếp qua quan hệ: mọi truy vấn PHẢI đi
  qua khoá quan hệ đã scope, không được `withoutGlobalScopes()` trần.
- Khi thêm bảng/đường đọc tenant mới: bắt buộc kèm test `MUST_NOT_LEAK` (điểm 5) — coi như một
  phần Definition of Done, không phải tuỳ chọn.
