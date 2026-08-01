<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\BelongsToProject;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Statement extends Model
{
    use BelongsToTenant, SoftDeletes, BelongsToProject;

    /**
     * Vốn từ `approval_status` — trục DUYỆT PHÁT HÀNH của BQL. Độc lập với `status`
     * (trục THU TIỀN: issued|partial|paid). Đừng trộn hai trục.
     */
    public const APPROVAL_PENDING = 'pending';

    public const APPROVAL_APPROVED = 'approved';

    public const APPROVAL_PUBLISHED = 'published';

    public const APPROVAL_REJECTED = 'rejected';

    protected $guarded = [];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'issued_at' => 'datetime',
        'published_at' => 'datetime',
        'viewed_at' => 'datetime',
        'due_date' => 'date',
    ];

    /**
     * Bảng kê CƯ DÂN ĐƯỢC THẤY — định nghĩa DUY NHẤT một chỗ.
     *
     * Quyết định chủ dự án D1 (2026-07-31, `docs/BILLING_OWNER_DECISIONS_20260731.md`):
     * cư dân chỉ thấy khoản phí đã đi hết chuỗi kế toán nhập → trưởng ban duyệt → phát hành.
     *
     * Đòi CẢ HAI điều kiện, không chỉ một:
     *  - `approval_status = published` — BQL đã bấm phát hành
     *  - `published_at IS NOT NULL`    — có mốc thời gian phát hành thật
     * Lý do đòi cả hai: `approval_status` là chuỗi, một mass-update lỡ tay đặt được nó mà
     * không đặt `published_at`. Mốc thời gian là bằng chứng khó giả hơn.
     *
     * MỌI đường đọc dành cho cư dân phải dùng scope này. Thêm điều kiện lọc ở chỗ khác
     * là tạo ra định nghĩa thứ hai — và định nghĩa thứ hai sẽ lệch.
     */
    public function scopeVisibleToResident($query)
    {
        return $query
            ->where('approval_status', self::APPROVAL_PUBLISHED)
            ->whereNotNull('published_at');
    }

    /** Cư dân có được thấy bản ghi NÀY không — dùng cho đường show/deep-link. */
    public function isVisibleToResident(): bool
    {
        return $this->approval_status === self::APPROVAL_PUBLISHED
            && $this->published_at !== null;
    }

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }

    /**
     * Toà nhà của bảng kê — nguồn để suy `project_id` cho override phân bổ theo dự án
     * (Phase B4, `StatementLine::effectivePaymentPriority()`). Chưa khai quan hệ này
     * trước đây dù cột `building_id` đã dùng khắp nơi (`BelongsToProject` trait, seed,
     * test) — không có method thì Eloquent không coi `building` là relation, gọi
     * `$statement->building` sẽ ra lỗi thay vì lazy-load.
     */
    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function billingPeriod(): BelongsTo
    {
        return $this->belongsTo(BillingPeriod::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(StatementLine::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(StatementApproval::class);
    }

    /**
     * `paid_amount`/`status` là PHÉP CHIẾU từ `SUM(lines.paid_amount)` — không
     * bao giờ ghi tay (Phase B3, D3). Hai đường ghi tiền khác nhau chạm vào
     * dòng phí (`ResidentPaymentClaimReviewer` cho chứng từ chuyển khoản,
     * `ApartmentWalletService` cho ví căn hộ) đều phải gọi hàm này SAU khi sửa
     * `statement_lines.paid_amount`, để không có đường nào để bảng kê lệch với
     * tổng các dòng của chính nó.
     *
     * `status` dùng ĐÚNG 3 giá trị đo được thật trên DB (30/07): `paid` · `partial`
     * · `issued` — không có giá trị nào khác.
     */
    public function recomputePaidAmount(): void
    {
        $paid = (string) $this->lines()->sum('paid_amount');
        $total = (string) $this->total_amount;

        $this->forceFill([
            'paid_amount' => $paid,
            'status' => bccomp($paid, $total, 2) >= 0 && bccomp($total, '0', 2) > 0
                ? 'paid'
                : (bccomp($paid, '0', 2) > 0 ? 'partial' : 'issued'),
        ])->save();
    }
}
