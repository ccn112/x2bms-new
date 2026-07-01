<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Tier 4 — Yêu cầu sửa dữ liệu (có duyệt, có audit). */
class DataFixRequest extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = ['requested_change' => 'array', 'applied_at' => 'datetime'];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }
}
