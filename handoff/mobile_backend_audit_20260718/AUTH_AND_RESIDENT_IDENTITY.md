# AUTH_AND_RESIDENT_IDENTITY — Kiểm toán xác thực & danh tính cư dân

> Kiểm toán CHỈ ĐỌC ngày 2026-07-18. Không có secret/token/mật khẩu/.env (chỉ TÊN biến).
> Nền tảng: Laravel 13, Filament 5, laravel/sanctum, spatie/laravel-permission.

---

## 0. KẾT LUẬN NHANH (mobile readiness)

- **KHÔNG có API đăng nhập / cấp token cho app cư dân (Flutter) nào tồn tại.** `HasApiTokens` (Sanctum) có trên `User` và migration `personal_access_tokens` đã chạy, nhưng **không có bất kỳ lời gọi `createToken()`/`plainTextToken` nào trong mã ứng dụng** (grep chỉ trúng `vendor`, `composer.lock`, docs). 
- Toàn bộ `routes/api.php` hiện tại là **API quản trị SuperAdmin** (billing/integration/support), xác thực bằng **cookie session Filament** qua middleware `platform.admin`, KHÔNG bằng Sanctum token.
- Không có guard `api`, không có `config/sanctum.php`, không có route `sanctum/csrf-cookie`, không có `EnsureFrontendRequestsAreStateful` được cấu hình. Guard duy nhất là `web` (session). 
- "OTP" duy nhất là công cụ **BQL đặt lại mật khẩu cư dân** dựa trên cache (`ResetsResidentPassword`), **KHÔNG phải luồng đăng nhập OTP**.
- => Muốn có app cư dân cần **xây mới toàn bộ**: guard/token issuance, endpoint login (mật khẩu và/hoặc OTP thật), quản lý thiết bị, và scope dữ liệu theo căn hộ (xem `TENANCY_AND_CONTEXT.md` §5).

---

## 1. Thực thể danh tính

### 1.1 `User` — `app/Models/User.php` (tài khoản đăng nhập TOÀN CỤC)
- Kế thừa `Authenticatable`, implements `FilamentUser`. Traits: `HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes`. **KHÔNG dùng `BelongsToTenant`** (danh tính toàn cục).
- `#[Fillable(...)]`: `tenant_id, project_id, building_id, name, title, is_platform_admin, email, password, account_type, phone, id_no, dob, gender, nationality, kyc_status, kyc_verified_at, avatar_path`.
- `#[Hidden(['password','remember_token'])]`. Casts: `password`→hashed, `is_platform_admin`→bool, `email_verified_at`/`kyc_verified_at`→datetime, `dob`→date.
- `account_type`: `'staff'` (mặc định) hoặc `'resident'` (migration `2026_06_30_000005`, cột default `'staff'`, comment `staff|resident`). Tài khoản `resident` là **toàn cục, tenant_id thường NULL**, có KYC.
- Phương thức khóa:
  - `isResident()` → `account_type === 'resident'`.
  - `isPlatformAdmin()` → cột `is_platform_admin`.
  - `isTenantOperator()` → tồn tại `UserRoleScope` scope_type=`tenant`.
  - `accessibleProjectIds()` → null (platform) | grant project ∪ `project_id`.
  - `residentMemberships()` → `hasMany(Resident)->withoutGlobalScope('tenant')` (mọi hồ sơ resident của người này xuyên tenant).
  - `canAccessPanel(Panel)` → `is_platform_admin || roles()->exists()` → **chặn 5M cư dân vào panel admin** (chỉ ~10k staff/admin có role vào được Filament).

### 1.2 `Resident` — `app/Models/Resident.php` (HỒ SƠ cư dân theo tenant)
- Traits: `BelongsToTenant, SoftDeletes, BelongsToProject`. `$guarded = []`.
- Là membership của một người trong MỘT tenant (do BQL nhập; tên có thể lệch với tài khoản gốc).
- Liên kết tài khoản đăng nhập toàn cục qua **`residents.user_id`** (nullable): `linkedUser()` = `belongsTo(User::class,'user_id')`.
- `residents.link_status` (`unlinked|suggested|linked`) + `residents.linked_at` (migration `2026_06_30_000005`). Nối tài khoản↔hồ sơ **bằng CCCD (`id_no`), KHÔNG bằng tên** (xem `app/Support/Identity/ResidentIdentityMatcher.php`, chỉ match `users` có `account_type='resident'`).
- Quan hệ: `emergencyContacts()`, `apartments()` (belongsToMany qua `resident_apartment_relations`, pivot `role,is_primary,start_date`), `apartmentRelations()` (hasMany), `building()`, `primaryRelation()`.

### 1.3 Nhân viên (employees)
- **Không có model `Employee`.** Thay bằng:
  - `StaffProfile` (`app/Models/StaffProfile.php`) — `BelongsToTenant, SoftDeletes`; `user()`, `department()`; casts `dob/hire_date`.
  - `EmployeeProjectAssignment` — `BelongsToTenant, SoftDeletes`; `employee()`=`belongsTo(StaffProfile,'employee_id')`, `project()`, `department()`, `assignedBy()`.
  - `EmployeeAssignmentHistory`. Nhân viên đăng nhập vẫn là `User` (`account_type='staff'`, có role spatie).

### 1.4 Thành viên hộ / liên hệ khẩn cấp
- `ResidentEmergencyContact` (`app/Models/ResidentEmergencyContact.php`) — `BelongsToTenant, SoftDeletes`; `resident()`. **Không có model "household member" riêng**; thành viên hộ được biểu diễn qua `resident_apartment_relations.role` (`owner|tenant|member`).

### 1.5 Danh tính toàn cục (addendum SaaS) — song song với `users`
- `GlobalUserAccount` (`app/Models/GlobalUserAccount.php`) — "Tài khoản gốc toàn hệ thống"; **KHÔNG scope tenant**; casts `metadata_json,first_registered_at,last_login_at`; `bindingRequests()`, `unitBindings()`. `account_type` (migration `2026_07_01_000020`): `public_user|resident|employee|contractor|vendor|platform_admin`.
- `ResidentBindingRequest` — yêu cầu gắn tài khoản gốc vào căn hộ/tòa (BQL/Company duyệt); `BelongsToTenant, SoftDeletes`; `account()`,`apartment()`,`reviewer()`; casts gồm `evidence_files_json`.
- `ResidentUnitBinding` — liên kết đã duyệt (một user nhiều căn); `BelongsToTenant, SoftDeletes`; `account()`,`apartment()`; casts `starts_at/ends_at`.
- **Lưu ý kiến trúc:** tồn tại HAI mô hình danh tính chồng nhau — (a) `users(account_type=resident)` + `residents.user_id` (dùng bởi màn BQL hiện tại, matcher CCCD), và (b) `global_user_accounts` + `resident_unit_bindings` (addendum registry ở panel SuperAdmin). Trước khi xây API mobile cần chốt **cái nào là nguồn danh tính đăng nhập của cư dân** — hiện guard/`Authenticatable` chỉ là `User`, nên chỉ `users` mới đăng nhập được; `GlobalUserAccount` KHÔNG phải `Authenticatable`.

### 1.6 Bảng pivot `resident_apartment_relations`
Migration `2026_06_28_000007`: `id, tenant_id, resident_id, apartment_id, role(default 'owner': owner|tenant|member), is_primary(bool, default false), start_date(nullable), timestamps`. (Model có `BelongsToTenant`; KHÔNG có SoftDeletes.)

---

## 2. Cơ chế xác thực

### 2.1 Guard web (session Filament) — DUY NHẤT đang hoạt động
- `config/auth.php`: `defaults.guard = env('AUTH_GUARD','web')`; chỉ định nghĩa guard `web` (driver `session`, provider `users`→`User`). **Không có guard `api`/`sanctum`.**
- `config/session.php`: driver mặc định `database`; `same_site='lax'`; `domain=env(SESSION_DOMAIN)`.
- `bootstrap/app.php`: routing web + api; alias middleware `platform.admin` → `EnsurePlatformAdmin`; `redirectGuestsTo` = login Filament; JSON exception khi path `api/*`. **Không đăng ký middleware Sanctum stateful.**
- Đăng nhập thực tế = form login của panel Filament (`/admin`, `/hq`, `/sa`). Route `/` → redirect `/admin`.

### 2.2 API (Sanctum) — CÀI NHƯNG CHƯA DÙNG cho token
- `laravel/sanctum` có trong `composer.json`; `HasApiTokens` trên `User`; migration `database/migrations/2026_06_28_164949_create_personal_access_tokens_table.php` tồn tại.
- **Không có `config/sanctum.php`** (chưa publish) và **không có `createToken()` trong mã app** → chưa phát hành token nào.
- `routes/api.php`: 3 nhóm, tất cả `middleware('platform.admin')` prefix `platform/{billing,integrations,support}`. Comment ghi rõ *"Xác thực qua phiên Filament (actingAs trong test)"* — tức các endpoint này dựa cookie session, không phải Bearer token. `EnsurePlatformAdmin` chỉ kiểm `$request->user()?->isPlatformAdmin()`.
- => Không có endpoint nào phục vụ cư dân/nhân viên qua token.

### 2.3 OTP & đặt lại mật khẩu
- `app/Filament/Concerns/ResetsResidentPassword.php` — công cụ **trong panel BQL** để đặt lại mật khẩu cư dân, 4 phương thức: mật khẩu tạm (hiện 1 lần) · OTP · gửi link · tạo link copy (Zalo).
  - "OTP" = `random_int(0,999999)` 6 số, lưu **cache** key `resident_pwd_otp_{resident_id}` TTL 10 phút (`Cache::put`). **Đây KHÔNG phải luồng đăng nhập OTP** — không có endpoint nào xác minh OTP này để cấp phiên/token; nó chỉ để BQL đọc lại cho cư dân. Chỉ hoạt động khi `resident->linkedUser` tồn tại.
  - Link đặt lại dùng Password broker chuẩn Laravel (`Password::broker()->createToken`, bảng `password_reset_tokens`, hết hạn 60 phút — `config/auth.php passwords.users.expire=60`). Route web `/reset-password/{token}` + POST `/reset-password` (`ResidentPasswordResetController`) trong `routes/web.php`.
  - Kênh SMS/Zalo **chưa nối gateway** (`deliverResidentMail`: chỉ gửi khi channel=email hoặc có `config('mail.test_to')`); biến config tham chiếu: `mail.test_to`, `app.url`.
- **Không tìm thấy luồng OTP đăng nhập thật (login qua SĐT + OTP) nào.**

### 2.4 Phiên & quản lý thiết bị
- `LoginSession` (`app/Models/LoginSession.php`) — `BelongsToTenant`; casts `last_active_at,is_current`; `user()`. Model tồn tại (theo dõi phiên), nhưng KHÔNG phải cơ chế cấp token thiết bị cho mobile.
- `TwoFactorSetting` (`app/Models/TwoFactorSetting.php`) — model tối giản (`enabled,verified_at`), KHÔNG scope tenant; chưa thấy tích hợp vào luồng đăng nhập nào.
- **Không có bảng/model "devices" (đăng ký thiết bị/push token)** cho mobile.

---

## 3. Quan hệ người dùng ↔ căn hộ; đa căn / đa dự án / đa tenant

- **Tài khoản (1 người) ↔ nhiều hồ sơ resident**: `User::residentMemberships()` (xuyên tenant). Một người = 1 `users(account_type=resident, tenant_id NULL)` ↔ N `residents` (mỗi tenant một hồ sơ), nối bằng CCCD (`ResidentIdentityMatcher`).
- **Hồ sơ resident ↔ nhiều căn hộ**: `resident_apartment_relations` (pivot có `role`, `is_primary`, `start_date`). `primaryRelation()` = bản ghi `is_primary=true` (fallback bản đầu).
- **Đa tenant / đa dự án**: một người có thể là cư dân ở nhiều công ty (ví dụ seed: cùng CCCD → "Nguyễn Văn A"@Sunshine + "Anh A"@Đại Phúc). Vì `residents` có `BelongsToTenant`+`BelongsToProject`, mỗi hồ sơ nằm gọn trong tenant/dự án của nó; việc gộp "một người" phải đi qua `user_id` (bypass scope tenant có chủ đích).
- Mô hình addendum song song: `ResidentUnitBinding` (một `GlobalUserAccount` ↔ nhiều `apartment`, đã duyệt) — comment model: *"một user có thể nhiều căn"*.

---

## 4. Vai trò & phạm vi quyền (RBAC 3 tầng)

- **spatie/laravel-permission** trên `User` (`HasRoles`). `canAccessPanel` = có role hoặc platform admin.
- **`UserRoleScope`** (`app/Models/UserRoleScope.php`) — nguồn sự thật RBAC 3 tầng: một grant = (user, role, scope). `SCOPE_PLATFORM|TENANT|PROJECT|BUILDING` (building là scope mịn hơn, không phải một tầng). **KHÔNG dùng `BelongsToTenant`** (đọc trước khi có tenant context, nếu scope sẽ đệ quy). Quan hệ: `user,role(spatie),tenant,project,building`.
- **Policies** (`app/Policies/`): `Apartment, Building, Department, Floor, Project, Resident, Role, Tenant, User`. Tất cả theo mẫu `$authUser->can('<Ability>:<Model>')` (ví dụ `ViewAny:Resident`, `View:Resident`, ...). **Chỉ kiểm quyền spatie, KHÔNG kiểm quyền-theo-hàng/tenant** — cô lập theo hàng hoàn toàn dựa global scope (xem `TENANCY_AND_CONTEXT.md` §5.8).
- `Gate::before` bypass cho super_admin (theo comment `User.php`; cấu hình ở `AuthServiceProvider`/provider — không đọc trong phạm vi này nhưng được tham chiếu).
- Thông báo dùng RBAC riêng: `Notification::scopeVisibleTo($user)` + `canManageBy($user)` theo `owner_level` (`platform|tenant|project`).

---

## 5. Mức sẵn sàng cho Auth Mobile — nêu rõ

| Hạng mục | Trạng thái | Ghi chú |
|----------|-----------|---------|
| API đăng nhập cho app cư dân/Flutter | **KHÔNG có** | phải xây mới |
| Cấp token (Sanctum `createToken`) | **KHÔNG dùng ở mã app** | chỉ có trait + migration; chưa có endpoint issuance |
| Guard `api`/`sanctum` | **KHÔNG có** | chỉ guard `web` (session) |
| `config/sanctum.php` / stateful middleware | **KHÔNG có** | Sanctum chưa cấu hình |
| Login OTP thật (SĐT + OTP → phiên) | **KHÔNG có** | OTP hiện chỉ là cache reset-password của BQL, không cấp phiên |
| Đăng ký/quản lý thiết bị, push token | **KHÔNG có** | không có model devices |
| 2FA | Model có, **chưa nối luồng** | `TwoFactorSetting` chưa dùng |
| `LoginSession` (theo dõi phiên) | Có model | không phải cơ chế token mobile |
| Danh tính đăng nhập của cư dân | `users(account_type=resident)` (Authenticatable) | `GlobalUserAccount` KHÔNG phải Authenticatable — cần chốt nguồn danh tính trước khi build |
| Reset mật khẩu | Password broker chuẩn Laravel (web) | route `/reset-password`, hết hạn 60' |
| Kênh gửi SMS/Zalo | **Chưa nối gateway** | chỉ email (hoặc `mail.test_to`) hoạt động |

**Khuyến nghị khi xây auth mobile (không thực hiện, chỉ ghi nhận):**
1. Publish/cấu hình Sanctum, thêm guard/endpoint login cấp Bearer token cho `User(account_type=resident)`.
2. Xây luồng OTP đăng nhập thật (SĐT), tách khỏi công cụ reset của BQL; nối SMS/Zalo gateway.
3. Vì cư dân có `tenant_id` NULL → global scope `tenant` no-op cho họ → **mọi endpoint cư dân phải scope thủ công theo `resident_apartment_relations`/`ResidentUnitBinding`** (không dựa `CurrentContext` vì nó session-based). Xem `TENANCY_AND_CONTEXT.md` §5.1, §5.7.
4. Thêm quản lý thiết bị/push token; cân nhắc dùng/nâng cấp `LoginSession`, `TwoFactorSetting`.
5. Chốt một nguồn danh tính (users vs global_user_accounts) để tránh nhập nhằng liên kết.

---

## 6. Tệp đã đọc (tham chiếu)
- `app/Models/{User,Resident,ResidentApartmentRelation,ResidentEmergencyContact,GlobalUserAccount,ResidentBindingRequest,ResidentUnitBinding,LoginSession,TwoFactorSetting,UserRoleScope,StaffProfile,EmployeeProjectAssignment}.php`
- `app/Filament/Concerns/ResetsResidentPassword.php`
- `app/Http/Middleware/EnsurePlatformAdmin.php`
- `app/Policies/ResidentPolicy.php` (+ danh sách policies)
- `routes/api.php`, `routes/web.php`, `bootstrap/app.php`, `config/auth.php`, `config/session.php`
- `database/migrations/2026_06_28_000007_create_residents_and_structure.php`, `2026_06_30_000005_global_identity_and_resident_linking.php`, `2026_07_01_000020_create_global_account_binding.php`, `2026_06_28_164949_create_personal_access_tokens_table.php`
- `app/Support/Identity/ResidentIdentityMatcher.php` (tham chiếu)
