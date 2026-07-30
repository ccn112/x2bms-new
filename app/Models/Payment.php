<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasAttachments;
use App\Models\Concerns\HasComments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use BelongsToProject, BelongsToTenant, HasAttachments, HasComments, SoftDeletes;

    /** Cư dân khai báo đã chuyển khoản, đang chờ BQL duyệt. */
    public const STATUS_PENDING = 'pending';

    /** Đã xác nhận có tiền — chỉ trạng thái này mới được phân bổ vào hoá đơn. */
    public const STATUS_CONFIRMED = 'confirmed';

    /** BQL từ chối (chứng từ không khớp, ảnh không đọc được…) — PHẢI có lý do. */
    public const STATUS_REJECTED = 'rejected';

    /** Đã ghi nhận rồi nhưng bị đảo (chuyển khoản bị hoàn, ghi trùng…). */
    public const STATUS_REVERSED = 'reversed';

    public const STATUSES = [
        self::STATUS_PENDING, self::STATUS_CONFIRMED,
        self::STATUS_REJECTED, self::STATUS_REVERSED,
    ];

    /** BQL nhập tay trên web. */
    public const SOURCE_STAFF = 'staff';

    /** Cư dân tự chuyển khoản rồi nộp ảnh chứng từ qua app. */
    public const SOURCE_RESIDENT_APP = 'resident_app';

    /** Cổng thanh toán trả về (chưa dùng — chờ webhook từng kênh). */
    public const SOURCE_GATEWAY = 'gateway';

    /** Khớp từ sao kê ngân hàng. */
    public const SOURCE_RECONCILIATION = 'reconciliation';

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'ai_checked_at' => 'datetime',
        'ai_extraction' => 'array',
    ];

    /** Cư dân khai báo, BQL chưa quyết. */
    public function isAwaitingReview(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }

    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class);
    }

    public function method(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function receipt(): HasOne
    {
        return $this->hasOne(Receipt::class);
    }

    /** Cư dân đã nộp khai báo này (null với khoản BQL nhập tay). */
    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_id');
    }

    /** Người của BQL đã duyệt/từ chối. */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_id');
    }

    /** Hoá đơn mà cư dân KHAI là đang trả — chưa phải phân bổ đã chốt. */
    public function claimedStatement(): BelongsTo
    {
        return $this->belongsTo(Statement::class, 'claimed_statement_id');
    }

    public function claimedBankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'claimed_bank_account_id');
    }
}
