<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Yêu cầu sửa/đổi dữ liệu (admin-ops) — dùng cho màn BQL-02-07 "Yêu cầu đổi thông tin".
 * entity+target_id = đối tượng; requested_change = giá trị mới; before_snapshot = giá trị cũ khi áp dụng.
 */
class DataFixRequest extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = [
        'requested_change' => 'array',
        'before_snapshot' => 'array',
        'applied_at' => 'datetime',
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }
}
