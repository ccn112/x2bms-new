<?php

namespace App\Models;

use App\Models\Concerns\HasAttachments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Cư dân bấm nút báo lỗi trên một màn của app.
 *
 * Giá trị chính nằm ở `screen_key`: biết lỗi Ở ĐÂU mà không phải hỏi lại người báo.
 *
 * Không `BelongsToTenant` — người ẩn danh (chưa đăng nhập) cũng báo được, mà global
 * scope theo tenant sẽ ẩn sạch những báo cáo đó khỏi hàng chờ của `/sa`.
 */
class AppScreenReport extends Model
{
    use HasAttachments, SoftDeletes;

    public const STATUSES = ['new', 'triaged', 'in_progress', 'resolved', 'wont_fix'];

    public const KINDS = ['bug', 'idea', 'other'];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function isOpen(): bool
    {
        return ! in_array($this->status, ['resolved', 'wont_fix'], true);
    }
}
