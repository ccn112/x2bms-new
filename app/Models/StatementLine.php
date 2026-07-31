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
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
    ];

    /** Còn nợ của dòng phí này. */
    public function outstanding(): string
    {
        return bcsub((string) $this->amount, (string) ($this->paid_amount ?? 0), 2);
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
     * trước lại hết"). Family-based priority (B4, override theo dự án) sẽ THAY
     * bằng cách backfill `fee_types.payment_priority`, không đổi khoá này.
     */
    public function allocationSortKey(): string
    {
        $ft = $this->feeType;
        $critical = $ft && $ft->is_critical ? 0 : 1;
        $priority = $ft->payment_priority ?? 100;

        return sprintf('%d-%05d-%012d', $critical, $priority, $this->id);
    }

    public function statement(): BelongsTo
    {
        return $this->belongsTo(Statement::class);
    }

    public function feeType(): BelongsTo
    {
        return $this->belongsTo(FeeType::class);
    }
}
