<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ưu tiên phân bổ (`payment_priority`) của MỘT `fee_type`, riêng cho MỘT `project`
 * (Phase B4, D4-bis). Có mặt cho `(project_id, fee_type_id)` → override thắng
 * `fee_types.payment_priority` (mặc định tenant-wide); không có → dùng mặc định.
 *
 * Đọc từ `StatementLine::effectivePaymentPriority()` — CHỖ DUY NHẤT tra bảng này để
 * quyết định thứ tự phân bổ, giữ đúng nguyên tắc B3 "một khoá phân bổ dùng chung".
 */
class FeeTypePriorityOverride extends Model
{
    use BelongsToTenant, BelongsToProject;

    protected $guarded = [];

    protected $casts = [
        'payment_priority' => 'integer',
    ];

    public function feeType(): BelongsTo
    {
        return $this->belongsTo(FeeType::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
