<?php

declare(strict_types=1);

namespace App\Support\Import\Profiles;

use App\Models\AuditLog;
use App\Models\Resident;
use App\Support\Identity\ResidentIdentityMatcher;
use App\Support\Import\ImportColumnSpec;
use App\Support\Import\ImportProfile;
use App\Support\Import\RowIssue;
use App\Support\Import\RowNormalizers as N;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Import hồ sơ cư dân (BQL `/admin`) trên engine StagingImporter dùng chung.
 * Bọc scope tenant/building + audit + dedup — điểm vá bắt buộc so với importer
 * single-tenant của x1web. Chỉ ánh xạ các cột THẬT của bảng `residents`.
 */
class ResidentImportProfile implements ImportProfile
{
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
            new ImportColumnSpec('full_name', 'Họ tên', ['Ho ten', 'Full name'], required: true, normalizer: [N::class, 'string'], rules: ['string', 'max:255'], example: 'Nguyễn Văn An'),
            new ImportColumnSpec('id_no', 'CCCD/CMND', ['CCCD', 'So CCCD', 'CMND'], required: true, normalizer: [N::class, 'string'], rules: ['string', 'max:20'], example: '079090001234'),
            new ImportColumnSpec('phone', 'SĐT', ['So dien thoai', 'Phone', 'Điện thoại'], required: true, normalizer: [N::class, 'phone'], rules: ['string', 'max:20'], example: '0901234567'),
            new ImportColumnSpec('email', 'Email', ['E-mail'], normalizer: [N::class, 'email'], rules: ['email:rfc'], example: 'an@example.vn'),
            new ImportColumnSpec('gender', 'Giới tính', ['Gioi tinh'], normalizer: [N::class, 'string'], rules: ['string', 'max:20']),
            new ImportColumnSpec('dob', 'Ngày sinh', ['Ngay sinh', 'DOB'], normalizer: [N::class, 'date'], rules: ['date']),
            new ImportColumnSpec('nationality', 'Quốc tịch', ['Quoc tich'], normalizer: [N::class, 'string'], rules: ['string', 'max:100']),
            new ImportColumnSpec('id_issued_date', 'Ngày cấp', ['Ngay cap'], normalizer: [N::class, 'date'], rules: ['date']),
            new ImportColumnSpec('id_issued_place', 'Nơi cấp', ['Noi cap'], normalizer: [N::class, 'string'], rules: ['string', 'max:255']),
            new ImportColumnSpec('contact_address', 'Địa chỉ thường trú', ['Dia chi', 'Địa chỉ'], normalizer: [N::class, 'string'], rules: ['string', 'max:500']),
            new ImportColumnSpec('occupation', 'Nghề nghiệp', ['Nghe nghiep'], normalizer: [N::class, 'string'], rules: ['string', 'max:255']),
        ];
    }

    /** @return list<RowIssue> */
    public function validateRow(array $normalized, int $rowNumber, array $context): array
    {
        $issues = [];
        $idNo = $normalized['id_no'] ?? null;
        $buildingId = $context['building_id'] ?? null;

        // Trùng CCCD trong CÙNG tòa (scope) → cảnh báo (không chặn; có thể là cập nhật).
        if ($idNo && $buildingId) {
            $exists = Resident::query()
                ->where('building_id', $buildingId)
                ->where('id_no', $idNo)
                ->exists();
            if ($exists) {
                $issues[] = RowIssue::warning($rowNumber, "Đã tồn tại cư dân CCCD {$idNo} trong tòa này — sẽ tạo bản ghi mới, cân nhắc gộp.", ['id_no' => $idNo]);
            }
        }

        // Đã có tài khoản X2BMS toàn cục theo CCCD/SĐT → cảnh báo để liên kết.
        if ($this->matcher->findAccount($idNo, $normalized['phone'] ?? null)) {
            $issues[] = RowIssue::warning($rowNumber, 'CCCD/SĐT đã có tài khoản X2BMS — sẽ tự liên kết khi ghi.');
        }

        return $issues;
    }

    public function commitRow(array $normalized, array $context): Model
    {
        $payload = array_filter($normalized, fn ($v) => $v !== null && $v !== '');

        $payload['tenant_id'] = $context['tenant_id'];
        $payload['building_id'] = $context['building_id'] ?? null;
        $payload['code'] = 'CD-'.now()->format('ymd').'-'.Str::upper(Str::random(6));
        $payload['status'] = 'active';
        $payload['profile_status'] = 'cho_bo_sung';
        $payload['source'] = 'import';
        $payload['nationality'] ??= 'Việt Nam';

        $account = $this->matcher->findAccount($payload['id_no'] ?? null, $payload['phone'] ?? null);
        if ($account) {
            $payload['user_id'] = $account->id;
            $payload['link_status'] = 'linked';
            $payload['linked_at'] = now();
        }

        $resident = Resident::create($payload);

        AuditLog::create([
            'tenant_id' => $context['tenant_id'],
            'building_id' => $context['building_id'] ?? null,
            'user_id' => $context['user_id'] ?? null,
            'action' => 'resident.import',
            'subject_type' => Resident::class,
            'subject_id' => $resident->id,
            'description' => 'Nhập cư dân từ Excel: '.$resident->full_name
                .($account ? ' (liên kết tài khoản '.$account->name.')' : ''),
        ]);

        return $resident;
    }
}
