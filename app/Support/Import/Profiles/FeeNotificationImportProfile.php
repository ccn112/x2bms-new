<?php

declare(strict_types=1);

namespace App\Support\Import\Profiles;

use App\Enums\BillingFamily;
use App\Models\Apartment;
use App\Models\AuditLog;
use App\Models\BillingPeriod;
use App\Models\FeeType;
use App\Models\Statement;
use App\Models\StatementLine;
use App\Services\Billing\FeeNotificationCalculator;
use App\Support\Import\ImportColumnSpec;
use App\Support\Import\ImportProfile;
use App\Support\Import\RowIssue;
use App\Support\Import\RowNormalizers as N;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * OPTION A — Import "thông báo phí" theo MẪU CŨ (file phần mềm cũ đang dùng:
 * `import_thong_bao_phi-HPO-05.2026.xlsx`, 24 cột tiếng Việt).
 *
 * Khác `BillingChargeImportProfile` (Option B — mẫu MỚI canonical có sẵn cột
 * "Thành tiền" kế toán chốt): mẫu cũ KHÔNG có thành tiền → hệ thống TỰ TÍNH bằng
 * {@see FeeNotificationCalculator} theo đúng cách phần mềm cũ tính (cố định:
 * qty×đơn giá; lũy tiến: Σ định mức×đơn giá). Mục tiêu: kế toán giữ NGUYÊN file &
 * quy trình khi chuyển sang X2 (drop-in) — phục vụ di trú legacy→X2.
 *
 * Dùng chung engine `StagingImporter`. Giữ đúng 3 bất biến của Option B:
 *  1. Bảng kê sinh ra LUÔN `pending` (D1); đã qua pending thì CHẶN thêm dòng.
 *  2. KHÔNG chạm `paid_amount` khi dòng đã tồn tại (re-import idempotent).
 *  3. `total_amount` là phép chiếu Σ dòng.
 *
 * Khác biệt có chủ ý với Option B:
 *  - KHÔNG resolve subject theo biển số/đồng hồ: mẫu cũ đếm xe theo SỐ LƯỢNG gộp
 *    (không có biển số), điện/nước gộp theo căn — subject = null. Cấp 3 (tài sản)
 *    để lại cho mẫu mới / Phase sau.
 *  - Lưu `calculation_snapshot` = đầu vào gốc + cách tính để đối soát di trú.
 */
class FeeNotificationImportProfile implements ImportProfile
{
    private const MONEY_CAP = 5_000_000_000;

    public function __construct(private readonly FeeNotificationCalculator $calc = new FeeNotificationCalculator) {}

    public function importType(): string
    {
        return 'fee_notification';
    }

    public function rowType(): string
    {
        return 'fee_notification';
    }

    /** @return list<ImportColumnSpec> */
    public function columns(): array
    {
        return [
            new ImportColumnSpec('apartment_code', 'Mã căn hộ', ['Ma can ho', 'Căn hộ'], required: true, normalizer: [N::class, 'string'], rules: ['string', 'max:50'], example: 'A-0101'),
            new ImportColumnSpec('service_period_start', 'Ngày bắt đầu tính phí', ['Ngay bat dau tinh phi'], normalizer: [N::class, 'date'], rules: ['date'], example: '01/05/2026'),
            new ImportColumnSpec('service_period_end', 'Ngày kết thúc', ['Ngay ket thuc'], normalizer: [N::class, 'date'], rules: ['date'], example: '31/05/2026'),
            new ImportColumnSpec('period_code', 'Kỳ', ['Ky', 'Period'], required: true, normalizer: [N::class, 'string'], rules: ['string', 'max:10'], example: '202605'),
            new ImportColumnSpec('due_date', 'Hạn thanh toán', ['Han thanh toan'], normalizer: [N::class, 'date'], rules: ['date'], example: '25-05-2026'),
            new ImportColumnSpec('service_code', 'Mã dịch vụ', ['Ma dich vu'], required: true, normalizer: [N::class, 'string'], rules: ['string', 'max:50'], example: 'PQL'),
            new ImportColumnSpec('price_type', 'Loại giá áp dụng', ['Loai gia ap dung'], normalizer: [N::class, 'decimal'], example: '1'),
            new ImportColumnSpec('quantity', 'Số lượng sử dụng', ['So luong su dung', 'Số lượng'], normalizer: [N::class, 'decimal'], example: '1'),
            new ImportColumnSpec('fixed_unit_price', 'Đơn giá cố định', ['Don gia co dinh'], normalizer: [N::class, 'money'], example: '1911000'),
            new ImportColumnSpec('previous_reading', 'Chỉ số đầu', ['Chi so dau'], normalizer: [N::class, 'decimal'], example: '60'),
            new ImportColumnSpec('current_reading', 'Chỉ số cuối', ['Chi so cuoi'], normalizer: [N::class, 'decimal'], example: '62'),
            new ImportColumnSpec('tier1_qty', 'Định mức 1', ['Dinh muc 1'], normalizer: [N::class, 'decimal'], example: '2'),
            new ImportColumnSpec('tier1_price', 'Đơn giá 1', ['Don gia 1'], normalizer: [N::class, 'money'], example: '12075'),
            new ImportColumnSpec('tier2_qty', 'Định mức 2', ['Dinh muc 2'], normalizer: [N::class, 'decimal']),
            new ImportColumnSpec('tier2_price', 'Đơn giá 2', ['Don gia 2'], normalizer: [N::class, 'money']),
            new ImportColumnSpec('tier3_qty', 'Định mức 3', ['Dinh muc 3'], normalizer: [N::class, 'decimal']),
            new ImportColumnSpec('tier3_price', 'Đơn giá 3', ['Don gia 3'], normalizer: [N::class, 'money']),
            new ImportColumnSpec('discount', 'Giảm giá', ['Giam gia'], normalizer: [N::class, 'money'], example: '0'),
            new ImportColumnSpec('discount_type', 'Loại giảm giá', ['Loai giam gia'], normalizer: [N::class, 'string'], rules: ['string', 'max:50']),
            new ImportColumnSpec('note', 'Ghi chú', ['Ghi chu'], normalizer: [N::class, 'string'], rules: ['string', 'max:255']),
            new ImportColumnSpec('plate', 'Biển số xe', ['Bien so xe', 'BKS'], normalizer: [N::class, 'string'], rules: ['string', 'max:50']),
        ];
    }

    /** @return list<RowIssue> */
    public function validateRow(array $normalized, int $rowNumber, array $context): array
    {
        $issues = [];
        $tenantId = $context['tenant_id'];
        $buildingId = $context['building_id'] ?? null;

        $apartmentCode = $normalized['apartment_code'] ?? null;
        if (filled($apartmentCode) && $buildingId
            && ! Apartment::query()->where('building_id', $buildingId)->where('code', $apartmentCode)->exists()) {
            $issues[] = RowIssue::error($rowNumber, "Không tìm thấy căn hộ \"{$apartmentCode}\" trong dự án đang chọn.");
        }

        $periodCode = $normalized['period_code'] ?? null;
        if (filled($periodCode) && $buildingId
            && ! BillingPeriod::query()->where('tenant_id', $tenantId)->where('building_id', $buildingId)->where('code', $periodCode)->exists()) {
            $issues[] = RowIssue::error($rowNumber, "Chưa có kỳ phí \"{$periodCode}\" cho dự án này — tạo kỳ phí trước khi import.");
        }

        $serviceCode = $normalized['service_code'] ?? null;
        if (filled($serviceCode)
            && ! FeeType::query()->where('tenant_id', $tenantId)->where('code', $serviceCode)->exists()) {
            $issues[] = RowIssue::error($rowNumber, "Không tìm thấy mã dịch vụ \"{$serviceCode}\".");
        }

        // Tiền đầu vào phải số nguyên đồng (money() trả string khi có phần lẻ khác 0).
        foreach (['fixed_unit_price' => 'Đơn giá cố định', 'tier1_price' => 'Đơn giá 1', 'tier2_price' => 'Đơn giá 2', 'tier3_price' => 'Đơn giá 3', 'discount' => 'Giảm giá'] as $key => $fieldLabel) {
            $v = $normalized[$key] ?? null;
            if ($v !== null && ! is_int($v)) {
                $issues[] = RowIssue::error($rowNumber, "{$fieldLabel} \"{$v}\" — tiền đồng không có số lẻ.");
            }
        }

        // Tính thử để bắt lỗi trần/định dạng trước khi commit.
        $result = $this->calculate($normalized);
        if ($result['amount'] > self::MONEY_CAP) {
            $issues[] = RowIssue::error($rowNumber, 'Thành tiền tính ra vượt trần 5.000.000.000đ/dòng.');
        }

        return $issues;
    }

    public function commitRow(array $normalized, array $context): Model
    {
        $tenantId = $context['tenant_id'];
        $buildingId = $context['building_id'];
        $userId = $context['user_id'] ?? null;

        $apartment = Apartment::query()->where('building_id', $buildingId)->where('code', $normalized['apartment_code'])->firstOrFail();
        $period = BillingPeriod::query()->where('tenant_id', $tenantId)->where('building_id', $buildingId)->where('code', $normalized['period_code'])->firstOrFail();
        $feeType = FeeType::query()->where('tenant_id', $tenantId)->where('code', $normalized['service_code'])->firstOrFail();
        $family = BillingFamily::fromFeeType($feeType);

        $result = $this->calculate($normalized);
        $amount = $result['amount'];
        $snapshot = $result['snapshot'];

        $statement = Statement::query()
            ->where('apartment_id', $apartment->id)
            ->where('billing_period_id', $period->id)
            ->first();

        if ($statement === null) {
            $statement = Statement::create([
                'tenant_id' => $tenantId,
                'building_id' => $buildingId,
                'billing_period_id' => $period->id,
                'apartment_id' => $apartment->id,
                'code' => 'BK-'.$period->code.'-'.$apartment->code,
                'total_amount' => 0,
                'paid_amount' => 0,
                'status' => 'issued',
                'approval_status' => Statement::APPROVAL_PENDING,
                'issued_at' => now(),
                'created_by_user_id' => $userId,
            ]);
        } elseif ($statement->approval_status !== Statement::APPROVAL_PENDING) {
            throw new RuntimeException("Bảng kê {$statement->code} đã ở trạng thái duyệt \"{$statement->approval_status}\" — import không thể thêm dòng, dùng điều chỉnh riêng.");
        }

        $serviceStart = $normalized['service_period_start'] ?? $period->period_month ?? null;
        $serviceEnd = $normalized['service_period_end'] ?? null;
        $dueDate = $normalized['due_date'] ?? $period->due_date;

        // Số lượng hiển thị: cố định = Số lượng; lũy tiến = mức tiêu thụ (chỉ số cuối − đầu).
        $displayQty = $snapshot['method'] === 'metered'
            ? ($snapshot['consumption'] ?? null)
            : ($snapshot['quantity'] ?? null);
        $displayUnitPrice = $snapshot['method'] === 'fixed' && is_int($normalized['fixed_unit_price'] ?? null)
            ? $normalized['fixed_unit_price']
            : null;

        $line = StatementLine::firstOrNew([
            'statement_id' => $statement->id,
            'fee_type_id' => $feeType->id,
            'subject_type' => null,
            'subject_id' => null,
            'service_period_start' => $serviceStart,
        ]);
        $isNewLine = ! $line->exists;

        $line->fill([
            'fee_type' => $feeType->name,
            'fee_category' => $family->value,
            'service_period_end' => $serviceEnd,
            'quantity' => $displayQty,
            'unit_price' => $displayUnitPrice,
            'amount' => $amount,
            'due_date' => $dueDate,
            'source' => 'legacy_import',
            'calculation_snapshot' => $snapshot,
            'note' => $normalized['note'] ?? null,
        ]);
        if ($isNewLine) {
            $line->paid_amount = 0;
            $line->status = 'issued';
        }
        $line->save();

        $statement->update(['total_amount' => $statement->lines()->sum('amount')]);

        AuditLog::create([
            'tenant_id' => $tenantId,
            'building_id' => $buildingId,
            'user_id' => $userId,
            'action' => 'fee_notification.import',
            'subject_type' => StatementLine::class,
            'subject_id' => $line->id,
            'description' => "Nhập thông báo phí \"{$feeType->name}\" căn {$apartment->code} kỳ {$normalized['period_code']}: "
                .number_format($amount).'đ ('.$snapshot['method'].')',
        ]);

        return $line;
    }

    /** Tính thành tiền từ dòng đã normalize. @return array{amount:int, method:string, snapshot:array} */
    private function calculate(array $n): array
    {
        $priceType = (int) ($n['price_type'] ?? 0);
        $tiers = [
            ['qty' => $this->s($n['tier1_qty'] ?? null), 'price' => $this->s($n['tier1_price'] ?? null)],
            ['qty' => $this->s($n['tier2_qty'] ?? null), 'price' => $this->s($n['tier2_price'] ?? null)],
            ['qty' => $this->s($n['tier3_qty'] ?? null), 'price' => $this->s($n['tier3_price'] ?? null)],
        ];

        return $this->calc->compute(
            $priceType,
            $this->s($n['quantity'] ?? null),
            $this->s($n['fixed_unit_price'] ?? null),
            $tiers,
            $this->s($n['discount'] ?? null),
            isset($n['previous_reading']) ? $this->s($n['previous_reading']) : null,
            isset($n['current_reading']) ? $this->s($n['current_reading']) : null,
        );
    }

    private function s(mixed $v): string
    {
        if ($v === null || $v === '') {
            return '0';
        }

        return (string) $v;
    }
}
