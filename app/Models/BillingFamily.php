<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Catalog nhãn 5 billing family (bảng `billing_families`, P1c).
 *
 * LƯU Ý: đây là MODEL (bản ghi catalog), KHÁC `App\Enums\BillingFamily` (enum —
 * nguồn sự thật về mã + thứ tự phân bổ). Khi cần cả hai trong một file, dùng
 * alias rõ ràng. Model này chủ yếu để đọc nhãn hiển thị và làm đích FK canonical.
 */
class BillingFamily extends Model
{
    protected $table = 'billing_families';

    protected $guarded = [];

    protected $casts = [
        'priority' => 'integer',
        'requires_subject' => 'boolean',
        'system_locked' => 'boolean',
    ];

    public function getRouteKeyName(): string
    {
        return 'code';
    }
}
