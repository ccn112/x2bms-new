<?php

declare(strict_types=1);

namespace App\Support\Import\Profiles;

use App\Models\Apartment;
use App\Models\AuditLog;
use App\Models\Resident;
use App\Models\ResidentApartmentRelation;
use App\Support\Identity\ResidentIdentityMatcher;
use App\Support\Import\ImportColumnSpec;
use App\Support\Import\ImportProfile;
use App\Support\Import\RowIssue;
use App\Support\Import\RowNormalizers as N;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Import GỘP: căn hộ + cư dân + quan hệ trong 1 file (quyết định owner 2026-07-20).
 * Trên engine StagingImporter dùng chung; bọc scope tenant/building + audit + dedup.
 *
 * Triết lý trường bắt buộc (owner 2026-07-20): **chỉ Họ tên bắt buộc cứng**. Thiếu
 * CCCD/SĐT/Email vẫn nhập được nhưng gắn CẢNH BÁO + gợi ý AI (rule-based) và tự đặt
 * `profile_status='cho_bo_sung'`. Khớp thực tế ~88.9% hồ sơ thiếu CCCD.
 */
class ResidentImportProfile implements ImportProfile
{
    /** Chỉ các cột thuộc bảng `residents`. */
    private const RESIDENT_COLUMNS = [
        'full_name', 'id_no', 'phone', 'email', 'gender', 'dob',
        'nationality', 'id_issued_date', 'id_issued_place', 'contact_address', 'occupation',
    ];

    public function __construct(private readonly ResidentIdentityMatcher $matcher = new ResidentIdentityMatcher) {}

    public function importType(): string
    {
        return 'residents';
    }

    public function rowType(): string
    {
        return 'resident';
    }

    /** @return list<ImportColumnSpec> */
    public function columns(): array
    {
        return [
            new ImportColumnSpec('full_name', 'Họ tên', ['Ho ten', 'Full name'], required: true, normalizer: [N::class, 'name'], rules: ['string', 'max:255'], example: 'Nguyễn Văn An'),
            // Định danh — KHÔNG bắt buộc cứng (nới lỏng): thiếu/sai → cảnh báo, không chặn.
            new ImportColumnSpec('id_no', 'CCCD/CMND', ['CCCD', 'So CCCD', 'CMND'], normalizer: [N::class, 'idNo'], rules: ['string', 'max:20'], example: '079090001234'),
            new ImportColumnSpec('phone', 'SĐT', ['So dien thoai', 'Phone', 'Điện thoại'], normalizer: [N::class, 'phone'], rules: ['string', 'max:20'], example: '0901234567'),
            new ImportColumnSpec('email', 'Email', ['E-mail'], normalizer: [N::class, 'email'], example: 'an@example.vn'),
            new ImportColumnSpec('gender', 'Giới tính', ['Gioi tinh'], normalizer: [N::class, 'string'], rules: ['string', 'max:20']),
            new ImportColumnSpec('dob', 'Ngày sinh', ['Ngay sinh', 'DOB'], normalizer: [N::class, 'date'], rules: ['date']),
            new ImportColumnSpec('nationality', 'Quốc tịch', ['Quoc tich'], normalizer: [N::class, 'string'], rules: ['string', 'max:100']),
            new ImportColumnSpec('id_issued_date', 'Ngày cấp', ['Ngay cap'], normalizer: [N::class, 'date'], rules: ['date']),
            new ImportColumnSpec('id_issued_place', 'Nơi cấp', ['Noi cap'], normalizer: [N::class, 'string'], rules: ['string', 'max:255']),
            new ImportColumnSpec('contact_address', 'Địa chỉ thường trú', ['Dia chi', 'Địa chỉ'], normalizer: [N::class, 'string'], rules: ['string', 'max:500']),
            new ImportColumnSpec('occupation', 'Nghề nghiệp', ['Nghe nghiep'], normalizer: [N::class, 'string'], rules: ['string', 'max:255']),
            // Gộp căn hộ + quan hệ:
            new ImportColumnSpec('apartment_code', 'Mã căn hộ', ['Ma can ho', 'Căn hộ', 'Can ho', 'Apartment'], normalizer: [N::class, 'string'], rules: ['string', 'max:50'], example: 'A-0205'),
            new ImportColumnSpec('role', 'Vai trò', ['Vai tro', 'Role', 'Loại cư dân', 'Loai cu dan'], normalizer: [self::class, 'normalizeRole'], rules: ['in:owner,tenant,member'], example: 'Chủ sở hữu'),
        ];
    }

    /** Chuẩn hóa vai trò về owner|tenant|member (nhận cả nhãn tiếng Việt). */
    public static function normalizeRole(?string $value): ?string
    {
        $v = N::string($value);
        if ($v === null) {
            return null;
        }
        $key = Str::lower($v);

        return match (true) {
            str_contains($key, 'thuê'), str_contains($key, 'thue'), $key === 'tenant' => 'tenant',
            str_contains($key, 'thành viên'), str_contains($key, 'thanh vien'), $key === 'member' => 'member',
            str_contains($key, 'chủ'), str_contains($key, 'chu'), $key === 'owner' => 'owner',
            default => in_array($key, ['owner', 'tenant', 'member'], true) ? $key : null,
        };
    }

    /** @return list<RowIssue> — cảnh báo + GỢI Ý AI (rule-based), không chặn nhập. */
    public function validateRow(array $normalized, int $rowNumber, array $context): array
    {
        $issues = [];
        $idNo = $normalized['id_no'] ?? null;
        $phone = $normalized['phone'] ?? null;
        $email = $normalized['email'] ?? null;
        $buildingId = $context['building_id'] ?? null;

        // Chất lượng dữ liệu (sau khi đã tự chuẩn hóa) — cảnh báo để BQL rà lại.
        if (filled($idNo) && ! in_array(strlen((string) $idNo), [9, 12], true)) {
            $issues[] = RowIssue::warning($rowNumber, "CCCD/CMND \"{$idNo}\" có ".strlen((string) $idNo).' chữ số (không phải 9/12) — kiểm tra lại.');
        }
        if (filled($phone) && ! preg_match('/^0\d{9}$/', (string) $phone)) {
            $issues[] = RowIssue::warning($rowNumber, "SĐT \"{$phone}\" không đúng dạng 10 số VN — kiểm tra lại.");
        }
        if (filled($email) && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $issues[] = RowIssue::warning($rowNumber, "Email \"{$email}\" sai định dạng — kiểm tra lại (vẫn lưu để bổ sung).");
        }

        // Gợi ý AI: thiếu định danh.
        if (blank($idNo)) {
            $issues[] = RowIssue::warning($rowNumber, 'Gợi ý: thiếu CCCD → tạo hồ sơ "chờ bổ sung"; chưa định danh/liên kết tài khoản. Bổ sung CCCD sau ở màn chi tiết.');
        }
        if (blank($phone) && blank($email)) {
            $issues[] = RowIssue::warning($rowNumber, 'Gợi ý: thiếu cả SĐT & Email → không tạo được tài khoản đăng nhập; đánh dấu chưa kích hoạt.');
        }

        // Trùng CCCD trong cùng tòa → gợi ý gộp.
        if (filled($idNo) && $buildingId && Resident::query()->where('building_id', $buildingId)->where('id_no', $idNo)->exists()) {
            $issues[] = RowIssue::warning($rowNumber, "Gợi ý: đã tồn tại cư dân CCCD {$idNo} trong tòa — cân nhắc gộp thay vì tạo mới.", ['id_no' => $idNo]);
        }

        // Đã có tài khoản X2BMS toàn cục.
        if ($this->matcher->findAccount($idNo, $phone)) {
            $issues[] = RowIssue::warning($rowNumber, 'CCCD/SĐT đã có tài khoản X2BMS — sẽ tự liên kết khi ghi.');
        }

        // Căn hộ: báo nếu sẽ tạo mới / hoặc không gắn căn.
        $apCode = $normalized['apartment_code'] ?? null;
        if (filled($apCode) && $buildingId) {
            if (! Apartment::query()->where('building_id', $buildingId)->where('code', $apCode)->exists()) {
                $issues[] = RowIssue::warning($rowNumber, "Căn hộ {$apCode} chưa có → sẽ TẠO MỚI căn khi ghi.", ['apartment_code' => $apCode]);
            }
        } else {
            $issues[] = RowIssue::warning($rowNumber, 'Không có mã căn hộ → cư dân sẽ chưa gắn vào căn nào.');
        }

        return $issues;
    }

    public function commitRow(array $normalized, array $context): Model
    {
        $tenantId = $context['tenant_id'];
        $buildingId = $context['building_id'] ?? null;

        $payload = [];
        foreach (self::RESIDENT_COLUMNS as $c) {
            if (filled($normalized[$c] ?? null)) {
                $payload[$c] = $normalized[$c];
            }
        }
        $payload['tenant_id'] = $tenantId;
        $payload['building_id'] = $buildingId;
        $payload['code'] = 'CD-'.now()->format('ymd').'-'.Str::upper(Str::random(6));
        $payload['status'] = 'active';
        $payload['source'] = 'import';
        $payload['nationality'] ??= 'Việt Nam';

        // Nới lỏng: hồ sơ thiếu định danh (CCCD, hoặc cả SĐT+Email) → chờ bổ sung.
        $incomplete = blank($normalized['id_no'] ?? null) || (blank($normalized['phone'] ?? null) && blank($normalized['email'] ?? null));
        $payload['profile_status'] = $incomplete ? 'cho_bo_sung' : 'hoat_dong';

        // Tự liên kết tài khoản X2BMS toàn cục (nếu có CCCD/SĐT khớp).
        $account = $this->matcher->findAccount($normalized['id_no'] ?? null, $normalized['phone'] ?? null);
        if ($account) {
            $payload['user_id'] = $account->id;
            $payload['link_status'] = 'linked';
            $payload['linked_at'] = now();
        }

        $resident = Resident::create($payload);

        // Gộp: resolve-or-create căn hộ theo mã (trong tòa) + tạo quan hệ.
        $apartment = $this->resolveApartment($normalized['apartment_code'] ?? null, $tenantId, $buildingId);
        if ($apartment) {
            ResidentApartmentRelation::create([
                'tenant_id' => $tenantId,
                'resident_id' => $resident->id,
                'apartment_id' => $apartment->id,
                'role' => $normalized['role'] ?? 'owner',
                'is_primary' => true,
                'start_date' => now()->toDateString(),
            ]);
        }

        AuditLog::create([
            'tenant_id' => $tenantId,
            'building_id' => $buildingId,
            'user_id' => $context['user_id'] ?? null,
            'action' => 'resident.import',
            'subject_type' => Resident::class,
            'subject_id' => $resident->id,
            'description' => 'Nhập cư dân từ Excel: '.$resident->full_name
                .($apartment ? ' · căn '.$apartment->code.' ('.($normalized['role'] ?? 'owner').')' : '')
                .($account ? ' · liên kết TK '.$account->name : ''),
        ]);

        return $resident;
    }

    /** Tìm căn theo mã trong tòa; chưa có thì tạo căn tối thiểu (occupied). Không mã → null. */
    private function resolveApartment(?string $code, int $tenantId, ?int $buildingId): ?Apartment
    {
        $code = N::string($code);
        if ($code === null || $buildingId === null) {
            return null;
        }

        return Apartment::query()
            ->where('building_id', $buildingId)
            ->where('code', $code)
            ->first()
            ?? Apartment::create([
                'tenant_id' => $tenantId,
                'building_id' => $buildingId,
                'code' => $code,
                'status' => 'occupied',
            ]);
    }
}
