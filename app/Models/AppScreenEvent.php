<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Một lượt vào màn / một thao tác trong màn, do app gửi theo lô.
 *
 * Bảng THÔ, dự kiến hàng triệu dòng/ngày — đừng truy vấn báo cáo trực tiếp trên nó
 * (dùng `AppScreenDailyStat`), và nó bị dọn theo `config('telemetry.raw_retention_days')`.
 *
 * Không `BelongsToTenant`: thiết bị ẩn danh không thuộc tenant nào, mà global scope
 * theo tenant sẽ ẩn sạch những dòng đó khỏi báo cáo — đúng nhóm cần đếm nhất.
 * Phân phạm vi bằng `tenant_id`/`project_id` tường minh ở tầng truy vấn báo cáo.
 */
class AppScreenEvent extends Model
{
    public const UPDATED_AT = null;   // chỉ ghi, không sửa

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
