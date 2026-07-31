<?php

declare(strict_types=1);

namespace App\Support\Import\Profiles;

use App\Enums\BillingFamily;
use App\Models\Apartment;
use App\Models\AuditLog;
use App\Models\BillingPeriod;
use App\Models\FeeType;
use App\Models\ImportBatch;
use App\Models\Meter;
use App\Models\Statement;
use App\Models\StatementLine;
use App\Models\Vehicle;
use App\Support\Import\ImportColumnSpec;
use App\Support\Import\ImportProfile;
use App\Support\Import\RowIssue;
use App\Support\Import\RowNormalizers as N;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Import khoản phí cho kế toán — Phase B1 (reference slice), thay engine tính phí
 * (Phase 2 riêng, xem `BILLING_FEE_ENGINE_PHASE2_PLAN.md`). Kế toán nhập số đã tính
 * sẵn; hệ thống KHÔNG tự tính lại. Hợp đồng đầy đủ: `docs/BILLING_IMPORT_SPEC_20260731.md`.
 *
 * Trên engine `StagingImporter` dùng chung, theo đúng khuôn `ResidentImportProfile`.
 *
 * Ba điều dễ làm sai:
 * 1. Import KHÔNG BAO GIỜ tạo bảng kê `published` (D1) — statement mới luôn `pending`,
 *    và nếu statement đã tồn tại mà KHÔNG còn `pending` thì CHẶN ghi, không âm thầm
 *    thêm dòng vào một bảng kê cư dân đã thấy.
 * 2. KHÔNG chạm `paid_amount` khi dòng đã tồn tại (idempotent re-import không được xoá
 *    dấu vết đã thu, dù trên lý thuyết statement pending chưa có ai trả).
 * 3. Tài sản (`subject_type`/`subject_id`) sai là SAI TIỀN (tiền thừa vào ngăn của tài
 *    sản khác), không phải sai hiển thị — không khớp được thì CHẶN, không đoán.
 */
class BillingChargeImportProfile implements ImportProfile
{
    private const MONEY_CAP = 5_000_000_000;

    public function importType(): string
    {
        return 'billing_charges';
    }

    public function rowType(): string
    {
        return 'billing_charge';
    }

    /** @return list<ImportColumnSpec> */
    public function columns(): array
    {
        return [
            new ImportColumnSpec('apartment_code', 'Mã căn hộ', ['Ma can ho', 'Căn hộ', 'Can ho'], required: true, normalizer: [N::class, 'string'], rules: ['string', 'max:50'], example: 'A-0205'),
            new ImportColumnSpec('period_code', 'Kỳ phí', ['Ky phi', 'Period'], required: true, normalizer: [N::class, 'string'], rules: ['string', 'max:10'], example: '2026-07'),
            new ImportColumnSpec('fee_type_code', 'Mã loại phí', ['Ma loai phi'], required: true, normalizer: [N::class, 'string'], rules: ['string', 'max:50'], example: 'OTO'),
            new ImportColumnSpec('subject_ref', 'Tài sản', ['Tai san', 'BKS', 'Mã đồng hồ', 'Ma dong ho'], normalizer: [N::class, 'string'], rules: ['string', 'max:50'], example: '51K-838888'),
            new ImportColumnSpec('label', 'Tên khoản hiện cho cư dân', ['Ten khoan hien cho cu dan'], normalizer: [N::class, 'string'], rules: ['string', 'max:255'], example: 'Phí gửi ô tô 51K-838888'),
            new ImportColumnSpec('service_period_start', 'Kỳ dịch vụ từ', ['Ky dich vu tu'], normalizer: [N::class, 'date'], rules: ['date'], example: '2026-04-01'),
            new ImportColumnSpec('service_period_end', 'Kỳ dịch vụ đến', ['Ky dich vu den'], normalizer: [N::class, 'date'], rules: ['date'], example: '2026-04-30'),
            new ImportColumnSpec('previous_reading', 'Chỉ số đầu', ['Chi so dau'], normalizer: [N::class, 'decimal'], example: '1250'),
            new ImportColumnSpec('current_reading', 'Chỉ số cuối', ['Chi so cuoi'], normalizer: [N::class, 'decimal'], example: '1398'),
            new ImportColumnSpec('quantity', 'Số lượng', ['So luong'], normalizer: [N::class, 'decimal'], example: '148'),
            new ImportColumnSpec('unit_price', 'Đơn giá', ['Don gia'], normalizer: [N::class, 'money'], example: '3500'),
            new ImportColumnSpec('amount', 'Thành tiền', ['Thanh tien'], required: true, normalizer: [N::class, 'money'], example: '518000'),
            new ImportColumnSpec('vat_percent', 'VAT %', ['VAT'], normalizer: [N::class, 'decimal'], example: '8'),
            new ImportColumnSpec('discount', 'Miễn giảm', ['Mien giam'], normalizer: [N::class, 'money'], example: '0'),
            new ImportColumnSpec('due_date', 'Hạn thanh toán', ['Han thanh toan'], normalizer: [N::class, 'date'], rules: ['date'], example: '2026-07-15'),
            new ImportColumnSpec('note', 'Ghi chú', ['Ghi chu'], normalizer: [N::class, 'string'], rules: ['string', 'max:255']),
        ];
    }

    /** @return list<RowIssue> */
    public function validateRow(array $normalized, int $rowNumber, array $context): array
    {
        $issues = [];
        $tenantId = $context['tenant_id'];
        $buildingId = $context['building_id'] ?? null;

        $apartmentCode = $normalized['apartment_code'] ?? null;
        $apartment = null;
        if (filled($apartmentCode) && $buildingId) {
            $apartment = Apartment::query()->where('building_id', $buildingId)->where('code', $apartmentCode)->first();
            if (! $apartment) {
                $issues[] = RowIssue::error($rowNumber, "Không tìm thấy căn hộ \"{$apartmentCode}\" trong dự án đang chọn.");
            }
        }

        $periodCode = $normalized['period_code'] ?? null;
        if (filled($periodCode)) {
            if (! preg_match('/^\d{4}-\d{2}$/', $periodCode)) {
                $issues[] = RowIssue::error($rowNumber, "Kỳ phí \"{$periodCode}\" phải theo dạng YYYY-MM, ví dụ 2026-07.");
            } elseif ($buildingId && ! BillingPeriod::query()->where('tenant_id', $tenantId)->where('building_id', $buildingId)->where('code', $periodCode)->exists()) {
                $issues[] = RowIssue::error($rowNumber, "Chưa có kỳ phí \"{$periodCode}\" cho dự án này — tạo kỳ phí trước khi import.");
            }
        }

        $feeTypeCode = $normalized['fee_type_code'] ?? null;
        $feeType = null;
        if (filled($feeTypeCode)) {
            $feeType = FeeType::query()->where('tenant_id', $tenantId)->where('code', $feeTypeCode)->first();
            if (! $feeType) {
                $issues[] = RowIssue::error($rowNumber, "Không tìm thấy mã loại phí \"{$feeTypeCode}\".");
            }
        }

        if ($feeType && $apartment) {
            $issues = array_merge($issues, $this->validateSubject($feeType, $apartment, $normalized['subject_ref'] ?? null, $apartmentCode, $rowNumber));
        }

        // Tiền: money() trả string (không phải int) khi có phần lẻ khác 0 / định dạng hỏng.
        foreach (['amount' => 'Thành tiền', 'unit_price' => 'Đơn giá', 'discount' => 'Miễn giảm'] as $key => $fieldLabel) {
            $v = $normalized[$key] ?? null;
            if ($v !== null && ! is_int($v)) {
                $issues[] = RowIssue::error($rowNumber, "{$fieldLabel} \"{$v}\" — tiền đồng không có số lẻ.");
            }
        }

        $amount = $normalized['amount'] ?? null;
        if (is_int($amount)) {
            $isAdjustment = str_starts_with(trim((string) ($normalized['label'] ?? '')), '[ĐC]');
            if ($amount < 0 && ! $isAdjustment) {
                $issues[] = RowIssue::error($rowNumber, 'Thành tiền âm chỉ cho phép ở dòng điều chỉnh (nhãn có tiền tố "[ĐC]").');
            }
            if (abs($amount) > self::MONEY_CAP) {
                $issues[] = RowIssue::error($rowNumber, 'Thành tiền vượt trần 5.000.000.000đ/dòng.');
            }
        }

        $unitPrice = $normalized['unit_price'] ?? null;
        if (is_int($unitPrice) && $unitPrice < 0) {
            $issues[] = RowIssue::error($rowNumber, 'Đơn giá không được âm.');
        }

        // Cảnh báo, không chặn: kế toán có thể đã trừ miễn giảm hoặc làm tròn theo hợp đồng.
        $quantity = $normalized['quantity'] ?? null;
        if ($quantity !== null && is_int($unitPrice) && is_int($amount)) {
            $expected = (int) round($quantity * $unitPrice);
            if ($expected !== $amount) {
                $issues[] = RowIssue::warning($rowNumber, "Số lượng × đơn giá = {$expected}đ, khác Thành tiền ({$amount}đ) — có thể do miễn giảm hoặc làm tròn theo hợp đồng.");
            }
        }

        return $issues;
    }

    /** @return list<RowIssue> */
    private function validateSubject(FeeType $feeType, Apartment $apartment, ?string $subjectRef, ?string $apartmentCode, int $rowNumber): array
    {
        $family = BillingFamily::fromFeeType($feeType);

        if ($family === BillingFamily::Vehicle) {
            if (blank($subjectRef)) {
                return [RowIssue::error($rowNumber, "Loại phí \"{$feeType->code}\" là phí phương tiện — cột Tài sản (biển số) bắt buộc.")];
            }

            $plate = $this->normalizePlate($subjectRef);
            $exists = Vehicle::query()->where('apartment_id', $apartment->id)->get()
                ->contains(fn (Vehicle $v) => $this->normalizePlate($v->plate_no) === $plate);

            return $exists ? [] : [RowIssue::error($rowNumber, "BKS \"{$subjectRef}\" không thuộc căn {$apartmentCode}.")];
        }

        if (in_array($family, [BillingFamily::Electricity, BillingFamily::Water], true)) {
            $meterType = $family === BillingFamily::Electricity ? 'electric' : 'water';
            $meters = Meter::query()->where('apartment_id', $apartment->id)->where('type', $meterType)->get();

            if (filled($subjectRef)) {
                $matched = $meters->contains(fn (Meter $m) => mb_strtolower(trim((string) $m->code)) === mb_strtolower(trim($subjectRef)));

                return $matched ? [] : [RowIssue::error($rowNumber, "Mã đồng hồ \"{$subjectRef}\" không thuộc căn {$apartmentCode}.")];
            }

            if ($meters->count() > 1) {
                $label = $meterType === 'electric' ? 'điện' : 'nước';

                return [RowIssue::error($rowNumber, "Căn {$apartmentCode} có {$meters->count()} đồng hồ {$label} — phải nêu rõ cột Tài sản.")];
            }
        }

        return [];
    }

    public function commitRow(array $normalized, array $context): Model
    {
        $tenantId = $context['tenant_id'];
        $buildingId = $context['building_id'];
        $userId = $context['user_id'] ?? null;

        $apartment = Apartment::query()->where('building_id', $buildingId)->where('code', $normalized['apartment_code'])->firstOrFail();
        $period = BillingPeriod::query()->where('tenant_id', $tenantId)->where('building_id', $buildingId)->where('code', $normalized['period_code'])->firstOrFail();
        $feeType = FeeType::query()->where('tenant_id', $tenantId)->where('code', $normalized['fee_type_code'])->firstOrFail();
        $family = BillingFamily::fromFeeType($feeType);

        [$subjectType, $subjectId] = $this->resolveSubject($family, $apartment, $normalized['subject_ref'] ?? null);

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
            ]);
        } elseif ($statement->approval_status !== Statement::APPROVAL_PENDING) {
            // D1: import không bao giờ được thêm dòng vào bảng kê đã qua khỏi 'pending' —
            // cư dân có thể đã thấy (published) hoặc trưởng ban đang xử lý (approved/rejected).
            throw new RuntimeException("Bảng kê {$statement->code} đã ở trạng thái duyệt \"{$statement->approval_status}\" — import không thể thêm dòng, dùng điều chỉnh riêng.");
        }

        [$periodStart, $periodEnd] = $this->periodBounds($normalized['period_code']);
        $serviceStart = $normalized['service_period_start'] ?? $periodStart;
        $serviceEnd = $normalized['service_period_end'] ?? $periodEnd;
        $dueDate = $normalized['due_date'] ?? $period->due_date;
        $vat = $normalized['vat_percent'] ?? (float) ($feeType->vat_percent ?? 0);
        $label = $normalized['label'] ?? $feeType->name;

        $line = StatementLine::firstOrNew([
            'statement_id' => $statement->id,
            'fee_type_id' => $feeType->id,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'service_period_start' => $serviceStart,
        ]);
        $isNewLine = ! $line->exists;

        $line->fill([
            'fee_type' => $label,
            'fee_category' => $family->value,
            'service_period_end' => $serviceEnd,
            'quantity' => $normalized['quantity'] ?? null,
            'unit_price' => is_int($normalized['unit_price'] ?? null) ? $normalized['unit_price'] : null,
            'amount' => $normalized['amount'],
            'due_date' => $dueDate,
        ]);
        // KHÔNG chạm paid_amount/status khi dòng đã tồn tại — import là nghĩa vụ, không
        // phải tiền; re-import không được xoá dấu vết đã ghi nhận thu (spec §5.4).
        if ($isNewLine) {
            $line->paid_amount = 0;
            $line->status = 'issued';
        }
        $line->save();

        // total_amount là PHÉP CHIẾU (D3): luôn tính lại từ tổng các dòng, không cộng dồn tay.
        $statement->update(['total_amount' => $statement->lines()->sum('amount')]);

        AuditLog::create([
            'tenant_id' => $tenantId,
            'building_id' => $buildingId,
            'user_id' => $userId,
            'action' => 'billing_charge.import',
            'subject_type' => StatementLine::class,
            'subject_id' => $line->id,
            'description' => "Nhập khoản phí \"{$label}\" căn {$apartment->code} kỳ {$normalized['period_code']}: ".number_format((int) $normalized['amount']).'đ'
                .($vat > 0 ? " (VAT {$vat}%)" : ''),
        ]);

        return $line;
    }

    /** @return array{0:class-string|null,1:int|null} */
    private function resolveSubject(BillingFamily $family, Apartment $apartment, ?string $subjectRef): array
    {
        if (! $family->requiresSubject()) {
            return [null, null];
        }

        if ($family === BillingFamily::Vehicle) {
            $plate = $this->normalizePlate($subjectRef);
            $vehicle = Vehicle::query()->where('apartment_id', $apartment->id)->get()
                ->first(fn (Vehicle $v) => $this->normalizePlate($v->plate_no) === $plate);

            return $vehicle ? [Vehicle::class, $vehicle->id] : [null, null];
        }

        $meterType = $family === BillingFamily::Electricity ? 'electric' : 'water';
        $meters = Meter::query()->where('apartment_id', $apartment->id)->where('type', $meterType)->get();

        if ($subjectRef !== null) {
            $meter = $meters->first(fn (Meter $m) => mb_strtolower(trim((string) $m->code)) === mb_strtolower(trim($subjectRef)));

            return $meter ? [Meter::class, $meter->id] : [null, null];
        }

        return $meters->count() === 1 ? [Meter::class, $meters->first()->id] : [null, null];
    }

    private function normalizePlate(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return Str::upper(preg_replace('/[\s.\-]/', '', $value) ?? $value);
    }

    /** @return array{0:string,1:string} Ngày đầu/cuối tháng của `period_code` (YYYY-MM). */
    private function periodBounds(string $periodCode): array
    {
        $month = Carbon::createFromFormat('Y-m-d', $periodCode.'-01');

        return [$month->copy()->startOfMonth()->toDateString(), $month->copy()->endOfMonth()->toDateString()];
    }

    /**
     * Hoàn tác một lô đã ghi (spec §5.7) — CHỈ khi MỌI bảng kê bị ảnh hưởng còn
     * `pending`. Đã published thì từ chối cả lô (không hoàn tác một phần): cư dân có
     * thể đã thấy một số dòng, xoá lẻ sẽ để bảng kê ở trạng thái không ai định nghĩa.
     *
     * @return int Số dòng đã xoá.
     *
     * @throws RuntimeException khi có bảng kê không còn `pending`.
     */
    public function rollbackBatch(ImportBatch $batch, ?int $userId = null): int
    {
        $rows = $batch->rows()
            ->where('validation_status', 'imported')
            ->where('committed_entity_type', StatementLine::class)
            ->get();

        $lines = StatementLine::query()->with('statement')
            ->whereIn('id', $rows->pluck('committed_entity_id')->filter()->all())
            ->get();

        foreach ($lines as $line) {
            if ($line->statement && $line->statement->approval_status !== Statement::APPROVAL_PENDING) {
                throw new RuntimeException("Không thể hoàn tác: bảng kê {$line->statement->code} đã ở trạng thái \"{$line->statement->approval_status}\".");
            }
        }

        return DB::transaction(function () use ($lines, $rows, $batch, $userId): int {
            $statementIds = [];
            foreach ($lines as $line) {
                $statementIds[$line->statement_id] = true;
                $line->delete();
            }

            foreach (array_keys($statementIds) as $statementId) {
                $statement = Statement::find($statementId);
                $statement?->update(['total_amount' => $statement->lines()->sum('amount')]);
            }

            $rows->each(fn ($row) => $row->update([
                'validation_status' => 'valid',
                'committed_entity_type' => null,
                'committed_entity_id' => null,
            ]));

            $batch->update(['rolled_back_at' => now(), 'rolled_back_by' => $userId]);

            return $lines->count();
        });
    }
}
