<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StatementLine extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'legacy_paid_amount' => 'decimal:2',
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'calculation_snapshot' => 'array',
    ];

    /** Còn nợ của dòng phí này. */
    public function outstanding(): string
    {
        return bcsub((string) $this->amount, (string) ($this->paid_amount ?? 0), 2);
    }

    /**
     * P1a / ADR-003 — Ledger tiền ĐÃ TRẢ của dòng phí, hợp nhất hai nguồn ghi tiền:
     *   1) `payment_allocations`  (chứng từ chuyển khoản — `ResidentPaymentClaimReviewer`)
     *   2) `apartment_wallet_transactions` direction=out, ref=dòng này (ví/D6 —
     *      `ApartmentWalletService`), đóng vai "credit application tạm thời" cho tới
     *      khi Phase 5 dựng `credit_applications`.
     *
     * READ-ONLY, không ghi gì. Không phụ thuộc tenant context (dùng
     * `withoutGlobalScopes` cho ví) để chạy đúng cả trong console reconcile.
     */
    public function ledgerPaidAmount(): string
    {
        $fromAllocations = (string) PaymentAllocation::query()
            ->where('statement_line_id', $this->id)
            ->sum('amount');

        $fromWallet = (string) ApartmentWalletTransaction::withoutGlobalScopes()
            ->where('reference_type', $this->getMorphClass())
            ->where('reference_id', $this->id)
            ->where('direction', 'out')
            ->where('status', 'confirmed')
            ->sum('amount');

        return bcadd($fromAllocations ?: '0', $fromWallet ?: '0', 2);
    }

    /**
     * Chốt `legacy_paid_amount` (phần `paid_amount` chưa có ledger backing) MỘT LẦN,
     * nếu chưa chốt. PHẢI gọi TRƯỚC khi writer thêm ledger row mới cho dòng này —
     * nếu chốt sau, `paid_amount − ledger` đã trừ mất phần vừa thêm → legacy thiếu.
     *
     * Idempotent: đã có giá trị thì trả về luôn, không tính lại.
     */
    public function ensureLegacyBase(): string
    {
        if ($this->legacy_paid_amount !== null) {
            return (string) $this->legacy_paid_amount;
        }

        $legacy = bcsub((string) ($this->paid_amount ?? 0), $this->ledgerPaidAmount(), 2);
        if (bccomp($legacy, '0', 2) < 0) {
            $legacy = '0';
        }

        $this->forceFill(['legacy_paid_amount' => $legacy])->save();

        return $legacy;
    }

    /**
     * P1a / ADR-003 — `paid_amount`/`status` là PHÉP CHIẾU:
     *   paid_amount = legacy_paid_amount + ledgerPaidAmount()
     *
     * Nơi DUY NHẤT được phép đặt `paid_amount` của dòng. Writer chỉ append ledger
     * row rồi gọi hàm này; cấm `paid_amount += …` bằng tay. Tự chốt legacy base
     * lazy nếu chưa có (an toàn khi command backfill chưa chạy cho dòng này).
     */
    public function recomputePaidFromLedger(): void
    {
        $legacy = $this->ensureLegacyBase();
        $paid = bcadd($legacy, $this->ledgerPaidAmount(), 2);

        $this->forceFill([
            'paid_amount' => $paid,
            'status' => bccomp($paid, (string) $this->amount, 2) >= 0 && bccomp((string) $this->amount, '0', 2) > 0
                ? 'paid'
                : (bccomp($paid, '0', 2) > 0 ? 'partial' : 'issued'),
        ])->save();
    }

    /** Chỉ những dòng CÒN NỢ (`paid_amount < amount`). */
    public function scopeOutstanding(Builder $query): Builder
    {
        return $query->whereColumn('paid_amount', '<', 'amount');
    }

    /**
     * Khoá thứ tự phân bổ tiền vào dòng phí — DUY NHẤT một chỗ (Phase B3):
     * ưu tiên (`fee_types.is_critical` trước, `payment_priority` tăng dần),
     * rồi tới dòng CŨ HƠN trước (`id` tăng dần — nợ cũ trả trước nợ mới).
     *
     * `ResidentPaymentClaimReviewer` (chứng từ chuyển khoản) và
     * `ApartmentWalletService` (ví căn hộ) đều PHẢI dùng khoá này — hai đường
     * ghi tiền khác nhau tự chọn thứ tự khác nhau là kết quả khó giải thích
     * cho cư dân ("sao trả tiền điện xong nợ quản lý vẫn còn mà nợ điện tháng
     * trước lại hết"). Phase B4: `payment_priority` không còn đọc thẳng cột
     * `fee_types.payment_priority` — đi qua {@see effectivePaymentPriority()}
     * để override theo dự án (nếu có) thắng mặc định tenant-wide.
     */
    public function allocationSortKey(): string
    {
        $ft = $this->feeType;
        $critical = $ft && $ft->is_critical ? 0 : 1;
        $priority = $this->effectivePaymentPriority();

        return sprintf('%d-%05d-%012d', $critical, $priority, $this->id);
    }

    /**
     * Ưu tiên phân bổ CÓ HIỆU LỰC cho dòng phí này (Phase B4, D4-bis):
     * override theo dự án (`fee_type_priority_overrides`) nếu dự án của bảng kê
     * này đã tự sắp thứ tự riêng, không thì về mặc định tenant-wide
     * (`fee_types.payment_priority`, đã backfill theo family bằng
     * `billing:backfill-fee-priority`).
     *
     * Không tìm ra `fee_type` hoặc không suy được dự án (dữ liệu cũ thiếu
     * `building_id`) → về mặc định `100`, giữ đúng hành vi trước B4.
     */
    public function effectivePaymentPriority(): int
    {
        $ft = $this->feeType;
        $tenantDefault = $ft->payment_priority ?? 100;

        if ($ft === null) {
            return $tenantDefault;
        }

        $projectId = $this->resolveProjectId();
        if ($projectId === null) {
            return $tenantDefault;
        }

        $override = FeeTypePriorityOverride::withoutGlobalScopes()
            ->where('project_id', $projectId)
            ->where('fee_type_id', $ft->id)
            ->value('payment_priority');

        return $override ?? $tenantDefault;
    }

    /**
     * Dự án của bảng kê này, suy qua `statement.building.project_id` — `StatementLine`
     * không mang `project_id` trực tiếp. Ưu tiên quan hệ ĐÃ NẠP SẴN (cả hai call site
     * B3/B4 đều eager-load `statement.building` trước khi sắp `allocationSortKey()`)
     * để không phát sinh N+1 khi sắp nhiều dòng.
     */
    private function resolveProjectId(): ?int
    {
        $statement = $this->relationLoaded('statement') ? $this->getRelation('statement') : $this->statement;
        if ($statement === null) {
            return null;
        }

        $building = $statement->relationLoaded('building') ? $statement->getRelation('building') : $statement->building;

        return $building?->project_id;
    }

    public function statement(): BelongsTo
    {
        return $this->belongsTo(Statement::class);
    }

    public function feeType(): BelongsTo
    {
        return $this->belongsTo(FeeType::class);
    }

    /** Cấp 3 của mô hình phí (D6): tài sản sinh ra phí — vehicles | meters | … */
    public function subject(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }
}
